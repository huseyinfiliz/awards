<?php

namespace HuseyinFiliz\Awards\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Award;

class ResultsPublishedBlueprint implements BlueprintInterface
{
    public Award $award;

    public function __construct(Award $award)
    {
        $this->award = $award;
    }

    /**
     * Get the user that sent the notification.
     */
    public function getFromUser(): ?User
    {
        return null;
    }

    /**
     * Get the model that is the subject of this activity.
     */
    public function getSubject(): Award
    {
        return $this->award;
    }

    /**
     * Get the data to be stored in the notification.
     */
    public function getData(): array
    {
        return [
            'awardId' => $this->award->id,
            'awardName' => $this->award->name,
            'awardSlug' => $this->award->slug,
        ];
    }

    /**
     * Get the serialized type of this activity.
     */
    public static function getType(): string
    {
        return 'awardsResultsPublished';
    }

    /**
     * Get the name of the model class for the subject of this activity.
     */
    public static function getSubjectModel(): string
    {
        return Award::class;
    }
}
