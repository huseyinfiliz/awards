<?php

namespace HuseyinFiliz\Awards\Tests\Unit;

use Carbon\Carbon;
use HuseyinFiliz\Awards\Models\Award;
use PHPUnit\Framework\TestCase;

class AwardModelTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    public function test_is_draft_returns_true_when_status_is_draft(): void
    {
        $award = new Award();
        $award->status = 'draft';

        $this->assertTrue($award->isDraft());
        $this->assertFalse($award->isActive());
        $this->assertFalse($award->hasEnded());
        $this->assertFalse($award->isPublished());
    }

    public function test_is_active_returns_true_when_status_is_active(): void
    {
        $award = new Award();
        $award->status = 'active';

        $this->assertFalse($award->isDraft());
        $this->assertTrue($award->isActive());
        $this->assertFalse($award->hasEnded());
        $this->assertFalse($award->isPublished());
    }

    public function test_has_ended_returns_true_when_status_is_ended(): void
    {
        $award = new Award();
        $award->status = 'ended';

        $this->assertFalse($award->isDraft());
        $this->assertFalse($award->isActive());
        $this->assertTrue($award->hasEnded());
        $this->assertFalse($award->isPublished());
    }

    public function test_is_published_returns_true_when_status_is_published(): void
    {
        $award = new Award();
        $award->status = 'published';

        $this->assertFalse($award->isDraft());
        $this->assertFalse($award->isActive());
        $this->assertFalse($award->hasEnded());
        $this->assertTrue($award->isPublished());
    }

    public function test_is_voting_open_returns_true_when_active_and_within_date_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-15 12:00:00'));

        $award = new Award();
        $award->status = 'active';
        $award->starts_at = Carbon::parse('2025-06-01 00:00:00');
        $award->ends_at = Carbon::parse('2025-06-30 23:59:59');

        $this->assertTrue($award->isVotingOpen());
    }

    public function test_is_voting_open_returns_false_when_not_active(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-15 12:00:00'));

        $award = new Award();
        $award->status = 'draft';
        $award->starts_at = Carbon::parse('2025-06-01 00:00:00');
        $award->ends_at = Carbon::parse('2025-06-30 23:59:59');

        $this->assertFalse($award->isVotingOpen());
    }

    public function test_is_voting_open_returns_false_when_before_start_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-05-15 12:00:00'));

        $award = new Award();
        $award->status = 'active';
        $award->starts_at = Carbon::parse('2025-06-01 00:00:00');
        $award->ends_at = Carbon::parse('2025-06-30 23:59:59');

        $this->assertFalse($award->isVotingOpen());
    }

    public function test_is_voting_open_returns_false_when_after_end_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-07-15 12:00:00'));

        $award = new Award();
        $award->status = 'active';
        $award->starts_at = Carbon::parse('2025-06-01 00:00:00');
        $award->ends_at = Carbon::parse('2025-06-30 23:59:59');

        $this->assertFalse($award->isVotingOpen());
    }

    public function test_is_voting_open_returns_true_on_exact_start_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-01 00:00:00'));

        $award = new Award();
        $award->status = 'active';
        $award->starts_at = Carbon::parse('2025-06-01 00:00:00');
        $award->ends_at = Carbon::parse('2025-06-30 23:59:59');

        $this->assertTrue($award->isVotingOpen());
    }

    public function test_is_voting_open_returns_true_on_exact_end_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-30 23:59:59'));

        $award = new Award();
        $award->status = 'active';
        $award->starts_at = Carbon::parse('2025-06-01 00:00:00');
        $award->ends_at = Carbon::parse('2025-06-30 23:59:59');

        $this->assertTrue($award->isVotingOpen());
    }

    public function test_can_show_votes_returns_true_when_published(): void
    {
        $award = new Award();
        $award->status = 'published';
        $award->show_live_votes = false;

        $this->assertTrue($award->canShowVotes());
    }

    public function test_can_show_votes_returns_true_when_active_with_live_votes_enabled(): void
    {
        $award = new Award();
        $award->status = 'active';
        $award->show_live_votes = true;

        $this->assertTrue($award->canShowVotes());
    }

    public function test_can_show_votes_returns_false_when_active_with_live_votes_disabled(): void
    {
        $award = new Award();
        $award->status = 'active';
        $award->show_live_votes = false;

        $this->assertFalse($award->canShowVotes());
    }

    public function test_can_show_votes_returns_false_when_draft(): void
    {
        $award = new Award();
        $award->status = 'draft';
        $award->show_live_votes = true;

        $this->assertFalse($award->canShowVotes());
    }

    public function test_can_show_votes_returns_false_when_ended_without_publish(): void
    {
        $award = new Award();
        $award->status = 'ended';
        $award->show_live_votes = false;

        $this->assertFalse($award->canShowVotes());
    }

    public function test_has_started_returns_true_when_past_start_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-15 12:00:00'));

        $award = new Award();
        $award->starts_at = Carbon::parse('2025-06-01 00:00:00');

        $this->assertTrue($award->hasStarted());
    }

    public function test_has_started_returns_false_when_before_start_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-05-15 12:00:00'));

        $award = new Award();
        $award->starts_at = Carbon::parse('2025-06-01 00:00:00');

        $this->assertFalse($award->hasStarted());
    }

    public function test_has_started_returns_true_on_exact_start_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-01 00:00:00'));

        $award = new Award();
        $award->starts_at = Carbon::parse('2025-06-01 00:00:00');

        $this->assertTrue($award->hasStarted());
    }

    public function test_get_effective_status_returns_ended_for_ended_status(): void
    {
        $award = new Award();
        $award->status = 'ended';

        $this->assertEquals('ended', $award->getEffectiveStatus());
    }

    public function test_get_effective_status_returns_published_for_published_status(): void
    {
        $award = new Award();
        $award->status = 'published';

        $this->assertEquals('published', $award->getEffectiveStatus());
    }

    public function test_get_effective_status_returns_draft_for_draft_status(): void
    {
        $award = new Award();
        $award->status = 'draft';

        $this->assertEquals('draft', $award->getEffectiveStatus());
    }

    public function test_get_effective_status_returns_active_when_within_date_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-15 12:00:00'));

        $award = new Award();
        $award->status = 'active';
        $award->ends_at = Carbon::parse('2025-06-30 23:59:59');

        $this->assertEquals('active', $award->getEffectiveStatus());
    }

    public function test_get_effective_status_returns_ended_when_active_but_past_end_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-07-15 12:00:00'));

        $award = new Award();
        $award->status = 'active';
        $award->ends_at = Carbon::parse('2025-06-30 23:59:59');

        $this->assertEquals('ended', $award->getEffectiveStatus());
    }

    public function test_get_effective_status_returns_active_when_no_end_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-07-15 12:00:00'));

        $award = new Award();
        $award->status = 'active';
        $award->ends_at = null;

        $this->assertEquals('active', $award->getEffectiveStatus());
    }
}
