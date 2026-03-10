<?php

namespace HuseyinFiliz\Awards\Api\Controller\Admin;

use Flarum\Http\RequestUtil;
use Flarum\Notification\NotificationSyncer;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use HuseyinFiliz\Awards\Models\Award;
use HuseyinFiliz\Awards\Models\Vote;
use HuseyinFiliz\Awards\Notification\ResultsPublishedBlueprint;

class PublishResultsController implements RequestHandlerInterface
{
    public function __construct(protected NotificationSyncer $notifications)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $award = Award::findOrFail($id);

        $award->status = 'published';
        $award->save();

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

        return new JsonResponse([
            'data' => [
                'type' => 'awards',
                'id' => (string) $award->id,
                'attributes' => [
                    'status' => $award->status,
                    'isPublished' => true,
                ],
            ],
        ]);
    }
}
