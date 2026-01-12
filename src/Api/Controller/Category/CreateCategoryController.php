<?php

namespace HuseyinFiliz\Awards\Api\Controller\Category;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\CategorySerializer;
use HuseyinFiliz\Awards\Models\Category;

class CreateCategoryController extends AbstractCreateController
{
    public $serializer = CategorySerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $relationships = Arr::get($request->getParsedBody(), 'data.relationships', []);

        $awardId = Arr::get($relationships, 'award.data.id');

        $category = Category::create([
            'award_id' => $awardId,
            'name' => Arr::get($data, 'name'),
            'slug' => Arr::get($data, 'slug') ?: Str::slug(Arr::get($data, 'name')),
            'description' => Arr::get($data, 'description'),
            'sort_order' => Arr::get($data, 'sortOrder', 0),
            'allow_other' => Arr::get($data, 'allowOther', false),
        ]);

        return $category;
    }
}
