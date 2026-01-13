<?php

namespace HuseyinFiliz\Awards\Api\Controller\Nominee;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\NomineeSerializer;
use HuseyinFiliz\Awards\Models\Nominee;

class UpdateNomineeController extends AbstractShowController
{
    public $serializer = NomineeSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $nominee = Nominee::findOrFail($id);

        if (Arr::has($data, 'name')) {
            $nominee->name = Arr::get($data, 'name');
        }
        if (Arr::has($data, 'description')) {
            $nominee->description = Arr::get($data, 'description');
        }
        if (Arr::has($data, 'slug')) {
            $nominee->slug = Arr::get($data, 'slug') ?: Str::slug($nominee->name);
        }
        if (Arr::has($data, 'imageUrl')) {
            $nominee->image_url = Arr::get($data, 'imageUrl');
        }
        if (Arr::has($data, 'metadata')) {
            $nominee->metadata = Arr::get($data, 'metadata');
        }
        if (Arr::has($data, 'sortOrder')) {
            $nominee->sort_order = Arr::get($data, 'sortOrder');
        }

        $nominee->save();

        return $nominee;
    }
}
