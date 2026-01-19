<?php

namespace HuseyinFiliz\Awards\Api\Controller\Admin;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Flarum\Notification\NotificationSyncer;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\AwardSerializer;
use HuseyinFiliz\Awards\Models\Award;
use HuseyinFiliz\Awards\Models\Vote;
use HuseyinFiliz\Awards\Notification\ResultsPublishedBlueprint;

class PublishResultsController extends AbstractShowController
{
    public $serializer = AwardSerializer::class;

    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $award = Award::findOrFail($id);

        // Update status to published
        $award->status = 'published';
        $award->save();

        // Notify all users who voted (single batch notification)
        $userIds = Vote::whereHas('category', function ($q) use ($award) {
            $q->where('award_id', $award->id);
        })->distinct('user_id')->pluck('user_id');

        $users = \Flarum\User\User::whereIn('id', $userIds)->get();

        if ($users->isNotEmpty()) {
            $this->notifications->sync(
                new ResultsPublishedBlueprint($award),
                $users->all()
            );
        }

        return $award;
    }
}
