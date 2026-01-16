<?php

namespace HuseyinFiliz\Awards\Tests\Integration\Api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use HuseyinFiliz\Awards\Models\Vote;

class VoteApiTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('huseyinfiliz-awards');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'otheruser', 'email' => 'other@machine.local', 'is_email_confirmed' => 1],
            ],
            'awards' => [
                ['id' => 1, 'name' => 'Test Award', 'slug' => 'test-award', 'year' => 2025, 'status' => 'active', 'starts_at' => Carbon::now()->subDay(), 'ends_at' => Carbon::now()->addMonth()],
                ['id' => 2, 'name' => 'Closed Award', 'slug' => 'closed-award', 'year' => 2025, 'status' => 'ended', 'starts_at' => Carbon::now()->subMonth(), 'ends_at' => Carbon::now()->subDay()],
            ],
            'award_categories' => [
                ['id' => 1, 'award_id' => 1, 'name' => 'Best Category', 'slug' => 'best-category', 'sort_order' => 1],
                ['id' => 2, 'award_id' => 2, 'name' => 'Closed Category', 'slug' => 'closed-category', 'sort_order' => 1],
            ],
            'award_nominees' => [
                ['id' => 1, 'category_id' => 1, 'name' => 'Nominee 1', 'slug' => 'nominee-1', 'sort_order' => 1],
                ['id' => 2, 'category_id' => 1, 'name' => 'Nominee 2', 'slug' => 'nominee-2', 'sort_order' => 2],
                ['id' => 3, 'category_id' => 2, 'name' => 'Closed Nominee', 'slug' => 'closed-nominee', 'sort_order' => 1],
            ],
            'award_votes' => [
                ['id' => 1, 'nominee_id' => 1, 'category_id' => 1, 'user_id' => 3],
            ],
            'group_permission' => [
                ['group_id' => 3, 'permission' => 'awards.view'],
                ['group_id' => 3, 'permission' => 'awards.vote'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function user_can_vote_for_nominee(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-votes', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-votes',
                        'attributes' => [
                            'nomineeId' => 1,
                            'categoryId' => 1,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('data', $body);
    }

    /**
     * @test
     */
    public function vote_replaces_previous_vote_in_single_vote_mode(): void
    {
        // First vote
        $this->send(
            $this->request('POST', '/api/award-votes', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-votes',
                        'attributes' => [
                            'nomineeId' => 1,
                            'categoryId' => 1,
                        ],
                    ],
                ],
            ])
        );

        // Second vote for different nominee
        $response = $this->send(
            $this->request('POST', '/api/award-votes', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-votes',
                        'attributes' => [
                            'nomineeId' => 2,
                            'categoryId' => 1,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        // Verify only one vote exists for user in category
        $votes = Vote::where('user_id', 2)->where('category_id', 1)->get();
        $this->assertEquals(1, $votes->count());
        $this->assertEquals(2, $votes->first()->nominee_id);
    }

    /**
     * @test
     */
    public function user_cannot_vote_when_voting_closed(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-votes', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-votes',
                        'attributes' => [
                            'nomineeId' => 3,
                            'categoryId' => 2,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_delete_own_vote(): void
    {
        // First create a vote
        $this->send(
            $this->request('POST', '/api/award-votes', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-votes',
                        'attributes' => [
                            'nomineeId' => 1,
                            'categoryId' => 1,
                        ],
                    ],
                ],
            ])
        );

        $vote = Vote::where('user_id', 2)->where('category_id', 1)->first();

        $response = $this->send(
            $this->request('DELETE', '/api/award-votes/' . $vote->id, [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertNull(Vote::find($vote->id));
    }

    /**
     * @test
     */
    public function user_cannot_delete_others_vote(): void
    {
        // Vote ID 1 belongs to user 3
        $response = $this->send(
            $this->request('DELETE', '/api/award-votes/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_list_own_votes(): void
    {
        // Create a vote first
        $this->send(
            $this->request('POST', '/api/award-votes', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-votes',
                        'attributes' => [
                            'nomineeId' => 1,
                            'categoryId' => 1,
                        ],
                    ],
                ],
            ])
        );

        $response = $this->send(
            $this->request('GET', '/api/award-votes', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('data', $body);
    }
}
