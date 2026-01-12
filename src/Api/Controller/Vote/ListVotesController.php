<?php

namespace HuseyinFiliz\Awards\Api\Controller\Vote;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\VoteSerializer;
use HuseyinFiliz\Awards\Models\Vote;

class ListVotesController extends AbstractListController
{
    public $serializer = VoteSerializer::class;

    public $include = ['nominee', 'category', 'category.award'];

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.vote');

        $query = Vote::where('user_id', $actor->id)
            ->with(['nominee', 'category.award']);

        if ($awardId = Arr::get($request->getQueryParams(), 'filter.award')) {
             $query->whereHas('category', function($q) use ($awardId) {
                 $q->where('award_id', $awardId);
             });
        }

        return $query->get();
    }
}
