<?php

namespace HuseyinFiliz\Awards\Tests\Integration\Api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use HuseyinFiliz\Awards\Models\Award;

class AwardApiTest extends TestCase
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
                ['id' => 1, 'name' => 'Test Award 2025', 'slug' => 'test-award-2025', 'year' => 2025, 'status' => 'active', 'starts_at' => Carbon::now()->subDay(), 'ends_at' => Carbon::now()->addMonth()],
                ['id' => 2, 'name' => 'Draft Award', 'slug' => 'draft-award', 'year' => 2025, 'status' => 'draft', 'starts_at' => Carbon::now(), 'ends_at' => Carbon::now()->addMonth()],
            ],
            'group_permission' => [
                ['group_id' => 3, 'permission' => 'awards.view'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function guest_cannot_list_awards(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/awards')
        );

        // Flarum returns 403 for permission denied, not 401
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_list_awards(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/awards', [
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
    public function user_cannot_create_award(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/awards', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'awards',
                        'attributes' => [
                            'name' => 'New Award',
                            'slug' => 'new-award',
                            'year' => 2025,
                            'status' => 'draft',
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
    public function admin_can_create_award(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/awards', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'awards',
                        'attributes' => [
                            'name' => 'New Award',
                            'slug' => 'new-award',
                            'year' => 2025,
                            'status' => 'draft',
                            'startsAt' => Carbon::now()->toIso8601String(),
                            'endsAt' => Carbon::now()->addMonth()->toIso8601String(),
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('New Award', $body['data']['attributes']['name']);
    }

    /**
     * @test
     */
    public function admin_can_update_award(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/awards/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'awards',
                        'attributes' => [
                            'name' => 'Updated Award Name',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Updated Award Name', $body['data']['attributes']['name']);
    }

    /**
     * @test
     */
    public function admin_can_delete_award(): void
    {
        $response = $this->send(
            $this->request('DELETE', '/api/awards/2', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        $this->assertNull(Award::find(2));
    }

    /**
     * @test
     */
    public function user_can_view_single_award(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/awards/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Test Award 2025', $body['data']['attributes']['name']);
    }
}
