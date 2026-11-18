<?php

namespace HuseyinFiliz\Awards\Tests\Integration\Api;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Nominee;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use HuseyinFiliz\Awards\Models\Vote;
use PHPUnit\Framework\Attributes\Test;

class SecurityRegressionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension('huseyinfiliz-awards');
        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'awards' => [
                ['id' => 1, 'name' => 'Public Award', 'slug' => 'public-award', 'status' => 'active'],
                ['id' => 2, 'name' => 'Draft Award', 'slug' => 'draft-award', 'status' => 'draft'],
            ],
            'award_categories' => [
                ['id' => 1, 'award_id' => 1, 'name' => 'Public Category', 'slug' => 'public', 'allow_other' => true],
                ['id' => 2, 'award_id' => 2, 'name' => 'Private Category', 'slug' => 'private'],
            ],
            'award_nominees' => [
                ['id' => 1, 'category_id' => 1, 'name' => 'First', 'slug' => 'first'],
                ['id' => 2, 'category_id' => 1, 'name' => 'Second', 'slug' => 'second'],
                ['id' => 3, 'category_id' => 2, 'name' => 'Private Nominee', 'slug' => 'private'],
            ],
            'group_permission' => [
                ['group_id' => 3, 'permission' => 'awards.view'],
                ['group_id' => 3, 'permission' => 'awards.vote'],
            ],
        ]);
    }

    #[Test]
    public function draft_children_are_hidden_from_readers_and_guests(): void
    {
        foreach ([2, null] as $actor) {
            foreach (['/award-categories/2', '/award-nominees/3'] as $path) {
                $response = $this->send($this->request('GET', '/api'.$path, ['authenticatedAs' => $actor]));
                $this->assertEquals(404, $response->getStatusCode());
            }
        }
    }

    #[Test]
    public function child_lists_and_includes_cannot_disclose_drafts(): void
    {
        foreach ([
            '/award-categories?include=nominees,award',
            '/award-nominees?include=category,category.award',
            '/award-categories?filter[award]=2',
            '/award-nominees?filter[category]=2',
        ] as $path) {
            $response = $this->send($this->request('GET', '/api'.$path, ['authenticatedAs' => 2]));
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringNotContainsString('Private', (string) $response->getBody());
            $this->assertStringNotContainsString('Draft Award', (string) $response->getBody());
        }
    }

    #[Test]
    public function missing_view_permission_blocks_public_children_and_includes(): void
    {
        $this->database()->table('group_permission')->where('permission', 'awards.view')->delete();
        foreach (['/awards/1', '/award-categories/1', '/award-nominees/1', '/award-categories', '/award-nominees'] as $path) {
            $response = $this->send($this->request('GET', '/api'.$path, ['authenticatedAs' => 2]));
            $this->assertContains($response->getStatusCode(), [403, 404]);
        }
        $this->submitVote(1);
        $response = $this->send($this->request('GET', '/api/award-votes?include=category,nominee', ['authenticatedAs' => 2]));
        $body = json_decode((string) $response->getBody(), true);
        $this->assertEmpty($body['included'] ?? []);
    }

    #[Test]
    public function managers_can_read_draft_children(): void
    {
        foreach (['/award-categories/2', '/award-nominees/3', '/award-categories?filter[award]=2', '/award-nominees?filter[category]=2'] as $path) {
            $response = $this->send($this->request('GET', '/api'.$path, ['authenticatedAs' => 1]));
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString('Private', (string) $response->getBody());
        }
    }

    #[Test]
    public function invalid_replacement_preserves_the_previous_vote(): void
    {
        $this->assertEquals(201, $this->submitVote(1)->getStatusCode());
        $response = $this->submitVote(2, ['unexpectedField' => true]);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals([1], Vote::where('user_id', 2)->pluck('nominee_id')->all());
    }

    #[Test]
    public function the_category_is_derived_from_the_nominee(): void
    {
        $this->assertEquals(201, $this->submitVote(1, ['categoryId' => 2])->getStatusCode());
        $this->assertEquals(1, Vote::where('user_id', 2)->firstOrFail()->category_id);
    }

    #[Test]
    public function pending_suggestions_reserve_slots_against_votes(): void
    {
        $this->assertEquals(201, $this->suggest()->getStatusCode());
        $this->assertEquals(422, $this->submitVote(1)->getStatusCode());
        $this->assertEquals(0, Vote::where('user_id', 2)->count());
    }

    #[Test]
    public function multi_vote_and_unlimited_modes_keep_their_semantics(): void
    {
        $this->setting('huseyinfiliz-awards.votes_per_category', 2);
        $this->assertEquals(201, $this->submitVote(1)->getStatusCode());
        $this->assertEquals(201, $this->submitVote(2)->getStatusCode());
        $this->assertEquals(422, $this->suggest()->getStatusCode());
        $this->assertEquals(2, Vote::where('user_id', 2)->count());
    }

    #[Test]
    public function unlimited_mode_accepts_votes_and_suggestions(): void
    {
        $this->setting('huseyinfiliz-awards.votes_per_category', 0);
        $this->assertEquals(201, $this->submitVote(1)->getStatusCode());
        $this->assertEquals(201, $this->submitVote(2)->getStatusCode());
        $this->assertEquals(201, $this->suggest()->getStatusCode());
        $this->assertEquals(2, Vote::where('user_id', 2)->count());
    }

    #[Test]
    public function approving_a_suggestion_converts_its_reserved_slot_once(): void
    {
        $this->assertEquals(201, $this->suggest()->getStatusCode());
        $id = OtherSuggestion::firstOrFail()->id;
        $this->assertEquals(200, $this->moderate($id, ['action' => 'approve'])->getStatusCode());
        $this->assertEquals(1, Vote::where('user_id', 2)->count());
        $this->assertEquals('approved', OtherSuggestion::findOrFail($id)->status);
        $this->assertEquals(422, $this->moderate($id, ['action' => 'approve'])->getStatusCode());
        $this->assertEquals(4, Nominee::count());
    }

    #[Test]
    public function invalid_moderation_has_no_side_effects(): void
    {
        $this->suggest();
        $id = OtherSuggestion::firstOrFail()->id;
        $this->assertEquals(400, $this->moderate($id, ['action' => 'approve', 'unknown' => true])->getStatusCode());
        $this->assertEquals('pending', OtherSuggestion::findOrFail($id)->status);
        $this->assertEquals(3, Nominee::count());
        $this->assertEquals(0, Vote::count());
    }

    #[Test]
    public function merging_into_another_category_is_rejected(): void
    {
        $this->suggest();
        $id = OtherSuggestion::firstOrFail()->id;
        $this->assertEquals(404, $this->moderate($id, ['action' => 'merge', 'mergeToNomineeId' => 3])->getStatusCode());
        $this->assertEquals('pending', OtherSuggestion::findOrFail($id)->status);
        $this->assertEquals(0, Vote::count());
    }

    #[Test]
    public function merging_into_the_same_category_creates_one_vote(): void
    {
        $this->suggest();
        $id = OtherSuggestion::firstOrFail()->id;
        $this->assertEquals(200, $this->moderate($id, ['action' => 'merge', 'mergeToNomineeId' => 1])->getStatusCode());
        $this->assertEquals(1, Vote::where('user_id', 2)->count());
        $this->assertEquals('merged', OtherSuggestion::findOrFail($id)->status);
    }

    #[Test]
    public function rejection_releases_the_reserved_slot(): void
    {
        $this->suggest();
        $id = OtherSuggestion::firstOrFail()->id;
        $this->assertEquals(200, $this->moderate($id, ['action' => 'reject'])->getStatusCode());
        $this->assertEquals(201, $this->submitVote(1)->getStatusCode());
    }

    #[Test]
    public function members_cannot_set_moderation_state(): void
    {
        $response = $this->suggest(['status' => 'approved', 'mergedToNomineeId' => 1]);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(0, OtherSuggestion::count());
    }

    #[Test]
    public function a_failed_insert_rolls_back_the_replacement_delete(): void
    {
        $this->submitVote(1);
        Vote::creating(function () {
            throw new \RuntimeException('Simulated persistence failure');
        });
        $this->assertEquals(500, $this->submitVote(2)->getStatusCode());
        $this->assertEquals([1], Vote::where('user_id', 2)->pluck('nominee_id')->all());
    }

    #[Test]
    public function quota_failure_rolls_back_suggestion_approval(): void
    {
        $this->suggest();
        $id = OtherSuggestion::firstOrFail()->id;
        // Simulate a legacy over-quota state or a limit reduced after submission.
        OtherSuggestion::create(['category_id' => 1, 'user_id' => 2, 'name' => 'Reserved', 'status' => 'pending']);
        $this->assertEquals(422, $this->moderate($id, ['action' => 'approve'])->getStatusCode());
        $this->assertEquals('pending', OtherSuggestion::findOrFail($id)->status);
        $this->assertEquals(3, Nominee::count());
        $this->assertEquals(0, Vote::count());
    }

    private function submitVote(int $nomineeId, array $extra = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->send($this->request('POST', '/api/award-votes', [
            'authenticatedAs' => 2,
            'json' => ['data' => ['type' => 'award-votes', 'attributes' => array_merge(['nomineeId' => $nomineeId], $extra)]],
        ]));
    }

    private function suggest(array $extra = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->send($this->request('POST', '/api/award-other-suggestions', [
            'authenticatedAs' => 2,
            'json' => ['data' => ['type' => 'award-other-suggestions', 'attributes' => array_merge(['categoryId' => 1, 'name' => 'Suggested nominee'], $extra)]],
        ]));
    }

    private function moderate(int $id, array $attributes): \Psr\Http\Message\ResponseInterface
    {
        return $this->send($this->request('PATCH', '/api/award-other-suggestions/'.$id, [
            'authenticatedAs' => 1,
            'json' => ['data' => ['type' => 'award-other-suggestions', 'attributes' => $attributes]],
        ]));
    }
}
