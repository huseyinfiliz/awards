<?php

namespace HuseyinFiliz\Awards\Tests\Integration\Api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use HuseyinFiliz\Awards\Models\Nominee;

class NomineeApiTest extends TestCase
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
            ],
            'award_categories' => [
                ['id' => 1, 'award_id' => 1, 'name' => 'Best Category', 'slug' => 'best-category', 'sort_order' => 1],
            ],
            'award_nominees' => [
                ['id' => 1, 'category_id' => 1, 'name' => 'Nominee 1', 'slug' => 'nominee-1', 'sort_order' => 1, 'vote_adjustment' => 0],
                ['id' => 2, 'category_id' => 1, 'name' => 'Nominee 2', 'slug' => 'nominee-2', 'sort_order' => 2, 'vote_adjustment' => 5],
            ],
            'group_permission' => [
                ['group_id' => 3, 'permission' => 'awards.view'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function user_can_list_nominees(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/award-nominees', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertCount(2, $body['data']);
    }

    /**
     * @test
     */
    public function user_cannot_create_nominee(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-nominees', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-nominees',
                        'attributes' => [
                            'categoryId' => 1,
                            'name' => 'New Nominee',
                            'slug' => 'new-nominee',
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
    public function admin_can_create_nominee(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-nominees', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'award-nominees',
                        'attributes' => [
                            'categoryId' => 1,
                            'name' => 'New Nominee',
                            'slug' => 'new-nominee',
                            'sortOrder' => 3,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('New Nominee', $body['data']['attributes']['name']);
    }

    /**
     * @test
     */
    public function admin_can_update_nominee(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/award-nominees/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'award-nominees',
                        'attributes' => [
                            'name' => 'Updated Nominee Name',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Updated Nominee Name', $body['data']['attributes']['name']);
    }

    /**
     * @test
     */
    public function admin_can_delete_nominee(): void
    {
        $response = $this->send(
            $this->request('DELETE', '/api/award-nominees/2', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        $this->assertNull(Nominee::find(2));
    }

    /**
     * @test
     */
    public function admin_can_adjust_nominee_votes(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-nominees/1/adjust-votes', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'adjustment' => 10,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $nominee = Nominee::find(1);
        $this->assertEquals(10, $nominee->vote_adjustment);
    }

    /**
     * @test
     */
    public function user_cannot_adjust_nominee_votes(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-nominees/1/adjust-votes', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'adjustment' => 10,
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
    public function user_can_view_single_nominee(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/award-nominees/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Nominee 1', $body['data']['attributes']['name']);
    }
}
