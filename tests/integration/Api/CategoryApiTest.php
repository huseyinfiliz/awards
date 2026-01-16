<?php

namespace HuseyinFiliz\Awards\Tests\Integration\Api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use HuseyinFiliz\Awards\Models\Category;

class CategoryApiTest extends TestCase
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
                ['id' => 1, 'award_id' => 1, 'name' => 'Best Category', 'slug' => 'best-category', 'sort_order' => 1, 'allow_other' => false],
                ['id' => 2, 'award_id' => 1, 'name' => 'Second Category', 'slug' => 'second-category', 'sort_order' => 2, 'allow_other' => true],
            ],
            'group_permission' => [
                ['group_id' => 3, 'permission' => 'awards.view'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function user_can_list_categories(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/award-categories', [
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
    public function user_cannot_create_category(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-categories', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'award-categories',
                        'attributes' => [
                            'awardId' => 1,
                            'name' => 'New Category',
                            'slug' => 'new-category',
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
    public function admin_can_create_category(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-categories', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'award-categories',
                        'attributes' => [
                            'awardId' => 1,
                            'name' => 'New Category',
                            'slug' => 'new-category',
                            'sortOrder' => 3,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('New Category', $body['data']['attributes']['name']);
    }

    /**
     * @test
     */
    public function admin_can_update_category(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/award-categories/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'award-categories',
                        'attributes' => [
                            'name' => 'Updated Category Name',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Updated Category Name', $body['data']['attributes']['name']);
    }

    /**
     * @test
     */
    public function admin_can_delete_category(): void
    {
        $response = $this->send(
            $this->request('DELETE', '/api/award-categories/2', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        $this->assertNull(Category::find(2));
    }

    /**
     * @test
     */
    public function user_can_view_single_category(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/award-categories/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Best Category', $body['data']['attributes']['name']);
    }
}
