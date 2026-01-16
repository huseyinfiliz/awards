<?php

namespace HuseyinFiliz\Awards\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use HuseyinFiliz\Awards\Models\Award;
use InvalidArgumentException;

class AwardSerializer extends AbstractSerializer
{
    protected $type = 'awards';

    protected function getDefaultAttributes($award): array
    {
        if (!($award instanceof Award)) {
            throw new InvalidArgumentException(
                get_class($this) . ' can only serialize instances of ' . Award::class
            );
        }

        $actor = $this->getActor();
        
        // Can view results:
        // - Published: everyone with awards.view
        // - Ended (not published): only awards.viewResults permission
        $canViewResults = $award->isPublished() 
            || ($award->hasEnded() && $actor->hasPermission('awards.viewResults'));

        return [
            'name' => $award->name,
            'slug' => $award->slug,
            'description' => $award->description,
            'year' => (int) $award->year,
            'startsAt' => $this->formatDate($award->starts_at),
            'endsAt' => $this->formatDate($award->ends_at),
            'status' => $award->status,
            'effectiveStatus' => $award->getEffectiveStatus(),
            'showLiveVotes' => (bool) $award->show_live_votes,
            'imageUrl' => $award->image_url,
            'isDraft' => $award->isDraft(),
            'isActive' => $award->isActive(),
            'hasEnded' => $award->hasEnded(),
            'isPublished' => $award->isPublished(),
            'isVotingOpen' => $award->isVotingOpen(),
            'canShowVotes' => $award->canShowVotes(),
            'canViewResults' => $canViewResults,
            'categoryCount' => $award->category_count,
            'nomineeCount' => $award->nominee_count,
            'voteCount' => $award->vote_count,
        ];
    }

    protected function categories($award)
    {
        return $this->hasMany($award, CategorySerializer::class);
    }
}
