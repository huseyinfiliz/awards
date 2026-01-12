<?php

namespace HuseyinFiliz\Awards\Api\Controller\Category;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\CategorySerializer;
use HuseyinFiliz\Awards\Models\Category;

class UpdateCategoryController extends AbstractShowController
{
    public $serializer = CategorySerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $category = Category::findOrFail($id);

        if (Arr::has($data, 'name')) {
            $category->name = Arr::get($data, 'name');
        }
        if (Arr::has($data, 'slug')) {
            $category->slug = Arr::get($data, 'slug') ?: Str::slug($category->name);
        }
        if (Arr::has($data, 'description')) {
            $category->description = Arr::get($data, 'description');
        }
        if (Arr::has($data, 'sortOrder')) {
            $category->sort_order = Arr::get($data, 'sortOrder');
        }
        if (Arr::has($data, 'allowOther')) {
            $category->allow_other = Arr::get($data, 'allowOther');
        }

        $category->save();

        return $category;
    }
}
