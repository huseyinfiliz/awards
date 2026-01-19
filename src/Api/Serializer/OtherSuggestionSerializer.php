<?php

namespace HuseyinFiliz\Awards\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use InvalidArgumentException;

class OtherSuggestionSerializer extends AbstractSerializer
{
    protected $type = 'award-other-suggestions';

    protected function getDefaultAttributes($suggestion): array
    {
        if (!($suggestion instanceof OtherSuggestion)) {
            throw new InvalidArgumentException(
                get_class($this) . ' can only serialize instances of ' . OtherSuggestion::class
            );
        }

        return [
            'name' => $suggestion->name,
            'status' => $suggestion->status,
            'createdAt' => $this->formatDate($suggestion->created_at),
        ];
    }

    protected function category($suggestion)
    {
        return $this->hasOne($suggestion, CategorySerializer::class);
    }

    protected function user($suggestion)
    {
        return $this->hasOne($suggestion, BasicUserSerializer::class);
    }

    protected function mergedToNominee($suggestion)
    {
        return $this->hasOne($suggestion, NomineeSerializer::class);
    }
}
