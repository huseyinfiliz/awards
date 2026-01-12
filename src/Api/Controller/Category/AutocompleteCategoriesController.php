<?php

namespace HuseyinFiliz\Awards\Api\Controller\Category;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\CategorySerializer;
use HuseyinFiliz\Awards\Models\Category;

class AutocompleteCategoriesController extends AbstractListController
{
    public $serializer = CategorySerializer::class;

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $query = Arr::get($request->getQueryParams(), 'filter.q', '');

        return Category::where('name', 'like', "%{$query}%")
            ->select('name')
            ->distinct()
            ->limit(10)
            ->get();
    }
}
