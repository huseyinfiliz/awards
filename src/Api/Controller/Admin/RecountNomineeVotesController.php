<?php

namespace HuseyinFiliz\Awards\Api\Controller\Admin;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\NomineeSerializer;
use HuseyinFiliz\Awards\Models\Nominee;

class RecountNomineeVotesController extends AbstractShowController
{
    public $serializer = NomineeSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $nominee = Nominee::findOrFail($id);

        // Just return fresh nominee - voteCount is calculated from award_votes table
        // This forces a recount from the database
        return $nominee->fresh();
    }
}
