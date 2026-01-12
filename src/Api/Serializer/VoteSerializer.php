<?php

namespace HuseyinFiliz\Awards\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use HuseyinFiliz\Awards\Models\Vote;
use InvalidArgumentException;

class VoteSerializer extends AbstractSerializer
{
    protected $type = 'award-votes';

    protected function getDefaultAttributes($vote): array
    {
        if (!($vote instanceof Vote)) {
            throw new InvalidArgumentException(
                get_class($this) . ' can only serialize instances of ' . Vote::class
            );
        }

        return [
            'createdAt' => $this->formatDate($vote->created_at),
        ];
    }

    protected function nominee($vote)
    {
        return $this->hasOne($vote, NomineeSerializer::class);
    }

    protected function category($vote)
    {
        return $this->hasOne($vote, CategorySerializer::class);
    }

    protected function user($vote)
    {
        return $this->hasOne($vote, BasicUserSerializer::class);
    }
}
