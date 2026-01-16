<?php

namespace HuseyinFiliz\Awards\Tests\Unit;

use HuseyinFiliz\Awards\Models\Award;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Award model status methods.
 *
 * Note: Tests involving date comparisons (isVotingOpen, hasStarted, getEffectiveStatus with dates)
 * are in integration tests because Eloquent date casting requires a database connection.
 */
class AwardModelTest extends TestCase
{
    /**
     * @test
     */
    public function is_draft_returns_true_when_status_is_draft(): void
    {
        $award = new Award();
        $award->status = 'draft';

        $this->assertTrue($award->isDraft());
        $this->assertFalse($award->isActive());
        $this->assertFalse($award->hasEnded());
        $this->assertFalse($award->isPublished());
    }

    /**
     * @test
     */
    public function is_active_returns_true_when_status_is_active(): void
    {
        $award = new Award();
        $award->status = 'active';

        $this->assertFalse($award->isDraft());
        $this->assertTrue($award->isActive());
        $this->assertFalse($award->hasEnded());
        $this->assertFalse($award->isPublished());
    }

    /**
     * @test
     */
    public function has_ended_returns_true_when_status_is_ended(): void
    {
        $award = new Award();
        $award->status = 'ended';

        $this->assertFalse($award->isDraft());
        $this->assertFalse($award->isActive());
        $this->assertTrue($award->hasEnded());
        $this->assertFalse($award->isPublished());
    }

    /**
     * @test
     */
    public function is_published_returns_true_when_status_is_published(): void
    {
        $award = new Award();
        $award->status = 'published';

        $this->assertFalse($award->isDraft());
        $this->assertFalse($award->isActive());
        $this->assertFalse($award->hasEnded());
        $this->assertTrue($award->isPublished());
    }

    /**
     * @test
     */
    public function can_show_votes_returns_true_when_published(): void
    {
        $award = new Award();
        $award->status = 'published';
        $award->show_live_votes = false;

        $this->assertTrue($award->canShowVotes());
    }

    /**
     * @test
     */
    public function can_show_votes_returns_true_when_active_with_live_votes_enabled(): void
    {
        $award = new Award();
        $award->status = 'active';
        $award->show_live_votes = true;

        $this->assertTrue($award->canShowVotes());
    }

    /**
     * @test
     */
    public function can_show_votes_returns_false_when_active_with_live_votes_disabled(): void
    {
        $award = new Award();
        $award->status = 'active';
        $award->show_live_votes = false;

        $this->assertFalse($award->canShowVotes());
    }

    /**
     * @test
     */
    public function can_show_votes_returns_false_when_draft(): void
    {
        $award = new Award();
        $award->status = 'draft';
        $award->show_live_votes = true;

        $this->assertFalse($award->canShowVotes());
    }

    /**
     * @test
     */
    public function can_show_votes_returns_false_when_ended_without_publish(): void
    {
        $award = new Award();
        $award->status = 'ended';
        $award->show_live_votes = false;

        $this->assertFalse($award->canShowVotes());
    }

    /**
     * @test
     */
    public function get_effective_status_returns_ended_for_ended_status(): void
    {
        $award = new Award();
        $award->status = 'ended';

        $this->assertEquals('ended', $award->getEffectiveStatus());
    }

    /**
     * @test
     */
    public function get_effective_status_returns_published_for_published_status(): void
    {
        $award = new Award();
        $award->status = 'published';

        $this->assertEquals('published', $award->getEffectiveStatus());
    }

    /**
     * @test
     */
    public function get_effective_status_returns_draft_for_draft_status(): void
    {
        $award = new Award();
        $award->status = 'draft';

        $this->assertEquals('draft', $award->getEffectiveStatus());
    }
}
