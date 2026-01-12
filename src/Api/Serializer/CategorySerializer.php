<?php

namespace HuseyinFiliz\Awards\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use HuseyinFiliz\Awards\Models\Category;
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

        return [
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'sortOrder' => (int) $category->sort_order,
            'totalVotes' => $category->total_votes,
            'allowOther' => (bool) $category->allow_other,
            'pendingSuggestionsCount' => $category->pendingSuggestions()->count(),
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
