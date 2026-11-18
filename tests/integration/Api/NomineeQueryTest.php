<?php

namespace HuseyinFiliz\Awards\Tests\Integration\Api;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Nominee;
use PHPUnit\Framework\Attributes\Test;

class NomineeQueryTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension('huseyinfiliz-awards');
        $this->prepareDatabase([
            User::class        => [$this->normalUser()],
            'awards'           => [['id' => 1, 'name' => 'Visible', 'slug' => 'visible', 'status' => 'active', 'show_live_votes' => true]],
            'award_categories' => [['id' => 1, 'award_id' => 1, 'name' => 'Category', 'slug' => 'category']],
            'award_nominees'   => [
                ['id' => 1, 'category_id' => 1, 'name' => 'Adjusted', 'slug' => 'adjusted', 'vote_adjustment' => 1],
                ['id' => 2, 'category_id' => 1, 'name' => 'Unadjusted', 'slug' => 'unadjusted'],
            ],
            'award_votes' => [
                ['category_id' => 1, 'nominee_id' => 1, 'user_id' => 1],
                ['category_id' => 1, 'nominee_id' => 2, 'user_id' => 2],
            ],
            'group_permission' => [['group_id' => 3, 'permission' => 'awards.view']],
        ]);
    }

    #[Test]
    public function preloaded_counts_preserve_adjustments_percentages_and_private_fields(): void
    {
        foreach ([1, 2] as $actor) {
            $response = $this->send($this->request('GET', '/api/award-nominees?filter[category]=1', ['authenticatedAs' => $actor]));
            $this->assertEquals(200, $response->getStatusCode());
            $items = collect(json_decode((string) $response->getBody(), true)['data'])->keyBy('id');
            $adjusted = $items['1']['attributes'];
            $this->assertEquals(2, $adjusted['voteCount']);
            $this->assertEquals(100, $adjusted['votePercentage']);
            $this->assertEquals(1, $items['2']['attributes']['voteCount']);
            $this->assertEquals(50, $items['2']['attributes']['votePercentage']);
            if ($actor === 1) {
                $this->assertEquals(1, $adjusted['realVoteCount']);
                $this->assertEquals(1, $adjusted['voteAdjustment']);
            } else {
                $this->assertArrayNotHasKey('realVoteCount', $adjusted);
                $this->assertArrayNotHasKey('voteAdjustment', $adjusted);
            }
        }
    }

    #[Test]
    public function nominee_list_query_count_does_not_grow_per_nominee(): void
    {
        $db = $this->database();
        $queryCounts = [];
        foreach ([2, 18] as $size) {
            if ($size > 2) {
                foreach (range(3, $size) as $id) {
                    Nominee::create(['category_id' => 1, 'name' => 'Nominee '.$id, 'slug' => 'nominee-'.$id]);
                }
            }
            $db->enableQueryLog();
            $db->flushQueryLog();
            $response = $this->send($this->request('GET', '/api/award-nominees?filter[category]=1&page[limit]=50', ['authenticatedAs' => 1]));
            $queries = $db->getQueryLog();
            $db->disableQueryLog();
            $db->flushQueryLog();
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertCount($size, json_decode((string) $response->getBody(), true)['data']);
            $individualCounts = array_filter($queries, fn ($query) => str_starts_with(strtolower($query['query']), 'select count(') && str_contains($query['query'], 'award_votes') && str_contains($query['query'], 'nominee_id'));
            $this->assertCount(0, $individualCounts);
            $queryCounts[] = count($queries);
        }
        $this->assertLessThanOrEqual($queryCounts[0] + 2, $queryCounts[1]);
    }
}
