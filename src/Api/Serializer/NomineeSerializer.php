<?php

namespace HuseyinFiliz\Awards\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use HuseyinFiliz\Awards\Models\Nominee;
use InvalidArgumentException;

class NomineeSerializer extends AbstractSerializer
{
    protected $type = 'award-nominees';

    protected function getDefaultAttributes($nominee): array
    {
        if (!($nominee instanceof Nominee)) {
            throw new InvalidArgumentException(
                get_class($this) . ' can only serialize instances of ' . Nominee::class
            );
        }

        $attributes = [
            'name' => $nominee->name,
            'description' => $nominee->description,
            'slug' => $nominee->slug,
            'imageUrl' => $nominee->image_url,
            'metadata' => $nominee->metadata,
            'sortOrder' => (int) $nominee->sort_order,
        ];

        $award = $nominee->category->award;
        if ($award && $award->canShowVotes()) {
            $attributes['voteCount'] = $nominee->vote_count;
            $attributes['votePercentage'] = $nominee->vote_percentage;
        }

        return $attributes;
    }

    protected function category($nominee)
    {
        return $this->hasOne($nominee, CategorySerializer::class);
    }
}
