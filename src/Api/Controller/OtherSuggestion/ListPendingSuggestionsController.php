<?php

namespace HuseyinFiliz\Awards\Api\Controller\OtherSuggestion;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\OtherSuggestionSerializer;
use HuseyinFiliz\Awards\Models\OtherSuggestion;

class ListPendingSuggestionsController extends AbstractListController
{
    public $serializer = OtherSuggestionSerializer::class;
    public $include = ['category', 'user'];

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $categoryId = Arr::get($request->getQueryParams(), 'filter.category');
        $awardId = Arr::get($request->getQueryParams(), 'filter.award');

        $query = OtherSuggestion::with($this->extractInclude($request))
            ->where('status', 'pending');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($awardId) {
            $query->whereHas('category', function ($q) use ($awardId) {
                $q->where('award_id', $awardId);
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
