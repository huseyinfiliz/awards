<?php

namespace HuseyinFiliz\Awards\Api\Controller\Admin;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\NomineeSerializer;
use HuseyinFiliz\Awards\Models\Nominee;
use HuseyinFiliz\Awards\Models\Vote;

class UpdateNomineeVotesController extends AbstractShowController
{
    public $serializer = NomineeSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $newVoteCount = (int) Arr::get($data, 'voteCount');

        $nominee = Nominee::findOrFail($id);
        $currentCount = $nominee->votes()->count();

        if ($newVoteCount > $currentCount) {
            // Add dummy votes (user_id = 0 for admin-added)
            $toAdd = $newVoteCount - $currentCount;
            for ($i = 0; $i < $toAdd; $i++) {
                Vote::create([
                    'nominee_id' => $nominee->id,
                    'category_id' => $nominee->category_id,
                    'user_id' => 0, // System/admin added
                ]);
            }
        } elseif ($newVoteCount < $currentCount) {
            // Remove votes (prefer removing admin-added first)
            $toRemove = $currentCount - $newVoteCount;
            Vote::where('nominee_id', $nominee->id)
                ->orderBy('user_id', 'asc') // user_id = 0 first
                ->limit($toRemove)
                ->delete();
        }

        return $nominee->fresh();
    }
}
