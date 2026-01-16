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
            $userPendingSuggestionsCount = OtherSuggestion::where('category_id', $category->id)
                ->where('user_id', $actor->id)
                ->where('status', 'pending')
                ->count();

            $userVoteIds = Vote::where('category_id', $category->id)
                ->where('user_id', $actor->id)
                ->pluck('nominee_id')
                ->toArray();
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
