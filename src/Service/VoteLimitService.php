<?php

namespace HuseyinFiliz\Awards\Service;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Vote;
use HuseyinFiliz\Awards\Models\OtherSuggestion;

class VoteLimitService
{
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get the votes per category limit from settings.
     * 0 = unlimited, 1+ = limit
     */
    public function getVotesPerCategory(): int
    {
        return (int) $this->settings->get('huseyinfiliz-awards.votes_per_category', 1);
    }

    /**
     * Check if votes are unlimited.
     */
    public function isUnlimited(): bool
    {
        return $this->getVotesPerCategory() === 0;
    }

    /**
     * Count user's existing votes in a category.
     */
    public function getUserVoteCount(int $categoryId, int $userId): int
    {
        return Vote::where('category_id', $categoryId)
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * Count user's pending suggestions in a category.
     */
    public function getUserPendingSuggestionCount(int $categoryId, int $userId): int
    {
        return OtherSuggestion::where('category_id', $categoryId)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->count();
    }

    /**
     * Get total slots used by user in a category (votes + pending suggestions).
     */
    public function getTotalUsedSlots(int $categoryId, int $userId): int
    {
        return $this->getUserVoteCount($categoryId, $userId)
            + $this->getUserPendingSuggestionCount($categoryId, $userId);
    }

    /**
     * Get available vote slots for user in a category.
     * Returns PHP_INT_MAX if unlimited.
     */
    public function getAvailableSlots(int $categoryId, int $userId): int
    {
        $limit = $this->getVotesPerCategory();

        if ($limit === 0) {
            return PHP_INT_MAX;
        }

        return max(0, $limit - $this->getTotalUsedSlots($categoryId, $userId));
    }

    /**
     * Check if user can vote/suggest in a category.
     */
    public function canVote(int $categoryId, int $userId): bool
    {
        return $this->getAvailableSlots($categoryId, $userId) > 0;
    }

    /**
     * Check if this is single-vote mode (replace existing vote).
     */
    public function isSingleVoteMode(): bool
    {
        return $this->getVotesPerCategory() === 1;
    }
}
