<?php

namespace HuseyinFiliz\Awards\Tests\Integration\Api;

use Flarum\Audit\AuditLog;
use Flarum\Audit\AuditLogger;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class AuditIntegrationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();
        AuditLogger::$testMode = true;
        $this->extension('flarum-audit', 'huseyinfiliz-awards');
        $this->prepareDatabase([
            'audit_log'               => [],
            User::class               => [$this->normalUser()],
            'awards'                  => [['id' => 1, 'name' => 'Award', 'slug' => 'award', 'status' => 'active']],
            'award_categories'        => [['id' => 1, 'award_id' => 1, 'name' => 'Category', 'slug' => 'category', 'allow_other' => true]],
            'award_nominees'          => [['id' => 1, 'category_id' => 1, 'name' => 'Nominee', 'slug' => 'nominee']],
            'award_other_suggestions' => [['id' => 1, 'category_id' => 1, 'user_id' => 2, 'name' => 'Private suggestion text', 'status' => 'pending']],
            'group_permission'        => [
                ['group_id' => 3, 'permission' => 'awards.view'],
                ['group_id' => 3, 'permission' => 'awards.vote'],
            ],
        ]);
    }

    #[Test]
    public function management_changes_are_attributed_to_the_actor(): void
    {
        $response = $this->send($this->request('PATCH', '/api/awards/1', [
            'authenticatedAs' => 1,
            'json'            => ['data' => ['type' => 'awards', 'attributes' => ['name' => 'Renamed award']]],
        ]));
        $this->assertEquals(200, $response->getStatusCode());
        $log = AuditLog::where('action', 'awards.award.updated')->firstOrFail();
        $this->assertEquals(1, $log->actor_id);
        $this->assertEquals(['award_id' => 1, 'fields' => ['name']], $log->payload);
        $this->assertContains('awards.results.published', AuditLogger::$registeredActions['huseyinfiliz-awards']);
    }

    #[Test]
    public function vote_adjustments_record_old_and_new_values(): void
    {
        $response = $this->send($this->request('PATCH', '/api/award-nominees/1/votes', [
            'authenticatedAs' => 1,
            'json'            => ['data' => ['attributes' => ['voteAdjustment' => 5]]],
        ]));
        $this->assertEquals(200, $response->getStatusCode());
        $log = AuditLog::where('action', 'awards.nominee.votes_adjusted')->firstOrFail();
        $this->assertEquals(1, $log->actor_id);
        $this->assertEquals(['nominee_id' => 1, 'old_adjustment' => 0, 'new_adjustment' => 5], $log->payload);
    }

    #[Test]
    public function publication_is_logged_only_when_status_changes(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $response = $this->send($this->request('POST', '/api/awards/1/publish', ['authenticatedAs' => 1]));
            $this->assertEquals(200, $response->getStatusCode());
        }
        $this->assertEquals(1, AuditLog::where('action', 'awards.results.published')->count());
    }

    #[Test]
    public function moderation_records_decisions_without_suggestion_text_or_ballots(): void
    {
        $response = $this->send($this->request('PATCH', '/api/award-other-suggestions/1', [
            'authenticatedAs' => 1,
            'json'            => ['data' => ['attributes' => ['action' => 'approve']]],
        ]));
        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $log = AuditLog::where('action', 'awards.suggestion.moderated')->firstOrFail();
        $this->assertEquals(1, $log->actor_id);
        $this->assertEquals(['suggestion_id' => 1, 'category_id' => 1, 'old_status' => 'pending', 'new_status' => 'approved'], $log->payload);
        $this->assertStringNotContainsString('Private suggestion text', AuditLog::all()->toJson());
        $this->assertEquals(0, AuditLog::where('action', 'like', 'awards.vote.%')->count());
    }

    #[Test]
    public function rolled_back_moderation_does_not_leave_a_success_log(): void
    {
        $this->database();
        \HuseyinFiliz\Awards\Models\OtherSuggestion::create(['category_id' => 1, 'user_id' => 2, 'name' => 'Reserved', 'status' => 'pending']);
        $response = $this->send($this->request('PATCH', '/api/award-other-suggestions/1', [
            'authenticatedAs' => 1,
            'json'            => ['data' => ['attributes' => ['action' => 'approve']]],
        ]));
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(0, AuditLog::where('action', 'like', 'awards.%')->count());
    }
}
