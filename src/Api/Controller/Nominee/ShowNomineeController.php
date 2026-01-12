<?php

namespace HuseyinFiliz\Awards\Api\Controller\Nominee;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\NomineeSerializer;
use HuseyinFiliz\Awards\Models\Nominee;

class ShowNomineeController extends AbstractShowController
{
    public $serializer = NomineeSerializer::class;

    public $include = ['category', 'category.award'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.view');

        $id = Arr::get($request->getQueryParams(), 'id');

        return Nominee::with($this->extractInclude($request))->findOrFail($id);
    }
}
