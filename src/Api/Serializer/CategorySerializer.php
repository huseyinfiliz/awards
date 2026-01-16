<?php

namespace HuseyinFiliz\Awards\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use HuseyinFiliz\Awards\Models\Category;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use HuseyinFiliz\Awards\Models\Vote;
use InvalidArgumentException;

class CategorySerializer extends AbstractSerializer
{
    protected $type = 'award-categories';

    // Static cache for user data to avoid N+1 queries within same request
    protected static $userVotesCache = [];
    protected static $userSuggestionsCache = [];
    protected static $cacheUserId = null;

    protected function getDefaultAttributes($category): array
    {
        if (!($category instanceof Category)) {
            throw new InvalidArgumentException(
                get_class($this) . ' can only serialize instances of ' . Category::class
            );
        }

        $actor = $this->getActor();
        $userPendingSuggestionsCount = 0;
        $userVoteIds = [];

        if ($actor && $actor->id) {
            // Reset cache if user changed
            if (self::$cacheUserId !== $actor->id) {
                self::$userVotesCache = [];
                self::$userSuggestionsCache = [];
                self::$cacheUserId = $actor->id;
            }

            // Load all user votes at once if not cached
            if (empty(self::$userVotesCache)) {
                $votes = Vote::where('user_id', $actor->id)
                    ->select('category_id', 'nominee_id')
                    ->get();

                foreach ($votes as $vote) {
                    self::$userVotesCache[$vote->category_id][] = $vote->nominee_id;
                }
            }

            // Load all user pending suggestions at once if not cached
            if (empty(self::$userSuggestionsCache)) {
                $suggestions = OtherSuggestion::where('user_id', $actor->id)
                    ->where('status', 'pending')
                    ->select('category_id')
                    ->get()
                    ->groupBy('category_id');

                foreach ($suggestions as $catId => $items) {
                    self::$userSuggestionsCache[$catId] = $items->count();
                }
            }

            $userVoteIds = self::$userVotesCache[$category->id] ?? [];
            $userPendingSuggestionsCount = self::$userSuggestionsCache[$category->id] ?? 0;
        }

        return [
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'sortOrder' => (int) $category->sort_order,
            'totalVotes' => $category->total_votes,
            'voteCount' => $category->total_votes,
            'nomineeCount' => $category->nominee_count,
            'allowOther' => (bool) $category->allow_other,
            'pendingSuggestionsCount' => $category->pendingSuggestions()->count(),
            'userPendingSuggestionsCount' => $userPendingSuggestionsCount,
            'userVoteIds' => $userVoteIds,
        ];
    }

    protected function award($category)
    {
        return $this->hasOne($category, AwardSerializer::class);
    }

    protected function nominees($category)
    {
        return $this->hasMany($category, NomineeSerializer::class);
    }
}
