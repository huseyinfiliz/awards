<?php

namespace HuseyinFiliz\Awards\Api\Controller\Nominee;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\NomineeSerializer;
use HuseyinFiliz\Awards\Models\Nominee;

class ListNomineesController extends AbstractListController
{
    public $serializer = NomineeSerializer::class;

    public $include = ['category', 'category.award'];

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.view');

        $filters = $request->getQueryParams()['filter'] ?? [];
        $query = Nominee::query();

        if ($categoryId = Arr::get($filters, 'category')) {
            $query->where('category_id', $categoryId);
        }

        if ($awardId = Arr::get($filters, 'award')) {
             $query->whereHas('category', function($q) use ($awardId) {
                 $q->where('award_id', $awardId);
             });
        }

        return $query->orderBy('sort_order')->get();
    }
}
