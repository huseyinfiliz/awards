<?php

namespace HuseyinFiliz\Awards\Tests\Integration\Api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use HuseyinFiliz\Awards\Models\OtherSuggestion;

class OtherSuggestionApiTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('huseyinfiliz-awards');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'awards' => [
                ['id' => 1, 'name' => 'Test Award', 'slug' => 'test-award', 'year' => 2025, 'status' => 'active', 'starts_at' => Carbon::now()->subDay(), 'ends_at' => Carbon::now()->addMonth()],
                ['id' => 2, 'name' => 'Closed Award', 'slug' => 'closed-award', 'year' => 2025, 'status' => 'ended', 'starts_at' => Carbon::now()->subMonth(), 'ends_at' => Carbon::now()->subDay()],
            ],
            'award_categories' => [
                ['id' => 1, 'award_id' => 1, 'name' => 'Best Category', 'slug' => 'best-category', 'sort_order' => 1, 'allow_other' => true],
                ['id' => 2, 'award_id' => 1, 'name' => 'No Other Category', 'slug' => 'no-other-category', 'sort_order' => 2, 'allow_other' => false],
                ['id' => 3, 'award_id' => 2, 'name' => 'Closed Category', 'slug' => 'closed-category', 'sort_order' => 1, 'allow_other' => true],
            ],
            'award_nominees' => [
                ['id' => 1, 'category_id' => 1, 'name' => 'Nominee 1', 'slug' => 'nominee-1', 'sort_order' => 1],
            ],
            'group_permission' => [
                ['group_id' => 3, 'permission' => 'awards.view'],
                ['group_id' => 3, 'permission' => 'awards.vote'],
            ],
            'settings' => [
                ['key' => 'huseyinfiliz-awards.votes_per_category', 'value' => '1'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function user_with_vote_permission_can_create_suggestion(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-other-suggestions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-other-suggestions',
                        'attributes' => [
                            'categoryId' => 1,
                            'name' => 'My Suggestion',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('My Suggestion', $body['data']['attributes']['name']);
        $this->assertEquals('pending', $body['data']['attributes']['status']);
    }

    /**
     * @test
     */
    public function user_cannot_create_suggestion_if_category_does_not_allow(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-other-suggestions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-other-suggestions',
                        'attributes' => [
                            'categoryId' => 2, // allow_other = false
                            'name' => 'My Suggestion',
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
    public function user_cannot_create_suggestion_if_voting_closed(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-other-suggestions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-other-suggestions',
                        'attributes' => [
                            'categoryId' => 3, // Belongs to closed award
                            'name' => 'My Suggestion',
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
    public function quota_enforcement_prevents_excess_suggestions(): void
    {
        // Create first suggestion (quota is 1)
        $this->send(
            $this->request('POST', '/api/award-other-suggestions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-other-suggestions',
                        'attributes' => [
                            'categoryId' => 1,
                            'name' => 'First Suggestion',
                        ],
                    ],
                ],
            ])
        );

        // Try to create second suggestion
        $response = $this->send(
            $this->request('POST', '/api/award-other-suggestions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-other-suggestions',
                        'attributes' => [
                            'categoryId' => 1,
                            'name' => 'Second Suggestion',
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
    public function user_without_vote_permission_cannot_create_suggestion(): void
    {
        // Remove vote permission for normal user
        $this->database()->table('group_permission')
            ->where('group_id', 3)
            ->where('permission', 'awards.vote')
            ->delete();

        $response = $this->send(
            $this->request('POST', '/api/award-other-suggestions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-other-suggestions',
                        'attributes' => [
                            'categoryId' => 1,
                            'name' => 'My Suggestion',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_list_own_suggestions(): void
    {
        // Create a suggestion first
        $this->send(
            $this->request('POST', '/api/award-other-suggestions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-other-suggestions',
                        'attributes' => [
                            'categoryId' => 1,
                            'name' => 'My Suggestion',
                        ],
                    ],
                ],
            ])
        );

        // Route is /mine for user's own suggestions
        $response = $this->send(
            $this->request('GET', '/api/award-other-suggestions/mine', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('data', $body);
    }

    /**
     * @test
     */
    public function user_cannot_create_suggestion_with_empty_name(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-other-suggestions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-other-suggestions',
                        'attributes' => [
                            'categoryId' => 1,
                            'name' => '   ', // Only whitespace
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
    public function user_cannot_create_suggestion_with_name_exceeding_max_length(): void
    {
        $longName = str_repeat('a', 300); // 300 characters, exceeds 255 limit

        $response = $this->send(
            $this->request('POST', '/api/award-other-suggestions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-other-suggestions',
                        'attributes' => [
                            'categoryId' => 1,
                            'name' => $longName,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
    }

}
