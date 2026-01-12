<?php

namespace HuseyinFiliz\Awards\Api\Controller\Category;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\CategorySerializer;
use HuseyinFiliz\Awards\Models\Category;

class ShowCategoryController extends AbstractShowController
{
    public $serializer = CategorySerializer::class;

    public $include = ['nominees', 'award'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.view');

        $id = Arr::get($request->getQueryParams(), 'id');

        return Category::with($this->extractInclude($request))->findOrFail($id);
    }
}
