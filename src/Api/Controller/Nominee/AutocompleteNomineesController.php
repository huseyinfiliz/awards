<?php

namespace HuseyinFiliz\Awards\Api\Controller\Nominee;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\NomineeSerializer;
use HuseyinFiliz\Awards\Models\Nominee;

class AutocompleteNomineesController extends AbstractListController
{
    public $serializer = NomineeSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $query = Arr::get($request->getQueryParams(), 'filter.q', '');

        return Nominee::where('name', 'like', "%{$query}%")
            ->select('name', 'image_url')
            ->distinct()
            ->limit(10)
            ->get();
    }
}
