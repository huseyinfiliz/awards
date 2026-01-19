<?php

namespace HuseyinFiliz\Awards\Tests\Integration\Api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class PermissionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('huseyinfiliz-awards');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'viewer', 'email' => 'viewer@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'voter', 'email' => 'voter@machine.local', 'is_email_confirmed' => 1],
            ],
            'awards' => [
                ['id' => 1, 'name' => 'Test Award', 'slug' => 'test-award', 'year' => 2025, 'status' => 'active', 'starts_at' => Carbon::now()->subDay(), 'ends_at' => Carbon::now()->addMonth()],
                ['id' => 2, 'name' => 'Published Award', 'slug' => 'published-award', 'year' => 2025, 'status' => 'published', 'starts_at' => Carbon::now()->subMonth(), 'ends_at' => Carbon::now()->subDay()],
            ],
            'award_categories' => [
                ['id' => 1, 'award_id' => 1, 'name' => 'Best Category', 'slug' => 'best-category', 'sort_order' => 1],
            ],
            'award_nominees' => [
                ['id' => 1, 'category_id' => 1, 'name' => 'Nominee 1', 'slug' => 'nominee-1', 'sort_order' => 1],
            ],
            'groups' => [
                ['id' => 10, 'name_singular' => 'Viewer', 'name_plural' => 'Viewers'],
                ['id' => 11, 'name_singular' => 'Voter', 'name_plural' => 'Voters'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 10],
                ['user_id' => 4, 'group_id' => 11],
            ],
            'group_permission' => [
                ['group_id' => 10, 'permission' => 'awards.view'],
                ['group_id' => 11, 'permission' => 'awards.view'],
                ['group_id' => 11, 'permission' => 'awards.vote'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function user_without_view_permission_cannot_list_awards(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/awards', [
                'authenticatedAs' => 2, // normal user without specific group
            ])
        );

        // Flarum returns 403 for permission denied
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_with_view_permission_can_list_awards(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/awards', [
                'authenticatedAs' => 3, // viewer group
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_without_vote_permission_cannot_vote(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-votes', [
                'authenticatedAs' => 3, // viewer group (no vote permission)
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

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_with_vote_permission_can_vote(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-votes', [
                'authenticatedAs' => 4, // voter group
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
    }

    /**
     * @test
     */
    public function user_without_manage_permission_cannot_create_award(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/awards', [
                'authenticatedAs' => 4, // voter group
                'json' => [
                    'data' => [
                        'type' => 'awards',
                        'attributes' => [
                            'name' => 'New Award',
                            'slug' => 'new-award',
                            'year' => 2025,
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
    public function admin_has_manage_permission(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/awards', [
                'authenticatedAs' => 1, // admin
                'json' => [
                    'data' => [
                        'type' => 'awards',
                        'attributes' => [
                            'name' => 'Admin Award',
                            'slug' => 'admin-award',
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
    }

    /**
     * @test
     */
    public function user_with_view_permission_can_view_published_results(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/awards/2', [
                'authenticatedAs' => 3, // viewer group
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('published', $body['data']['attributes']['status']);
    }

    /**
     * @test
     */
    public function guest_cannot_access_any_awards(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/awards')
        );

        // Flarum returns 403 for guests without permission
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_cannot_vote(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/award-votes', [
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

        // Guest POST requests may return 400 (bad request) or 403 (permission denied)
        // depending on how authentication middleware handles unauthenticated requests
        $this->assertContains($response->getStatusCode(), [400, 403]);
    }
}
