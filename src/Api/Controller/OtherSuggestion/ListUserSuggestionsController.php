<?php

namespace HuseyinFiliz\Awards\Api\Controller\OtherSuggestion;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\OtherSuggestionSerializer;
use HuseyinFiliz\Awards\Models\OtherSuggestion;

class ListUserSuggestionsController extends AbstractListController
{
    public $serializer = OtherSuggestionSerializer::class;
    public $include = ['category'];

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $categoryId = Arr::get($request->getQueryParams(), 'filter.category');

        $query = OtherSuggestion::with($this->extractInclude($request))
            ->where('user_id', $actor->id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
