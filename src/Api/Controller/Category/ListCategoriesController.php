<?php

namespace HuseyinFiliz\Awards\Api\Controller\Category;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\CategorySerializer;
use HuseyinFiliz\Awards\Models\Category;

class ListCategoriesController extends AbstractListController
{
    public $serializer = CategorySerializer::class;

    public $include = ['nominees', 'award'];

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.view');

        $filters = $request->getQueryParams()['filter'] ?? [];
        $query = Category::query();

        if ($awardId = Arr::get($filters, 'award')) {
            $query->where('award_id', $awardId);
        }

        return $query->orderBy('sort_order')->get();
    }
}
