<?php

namespace HuseyinFiliz\Awards\Api\Controller\Nominee;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\NomineeSerializer;
use HuseyinFiliz\Awards\Models\Nominee;

/**
 * @TODO: Remove this in favor of one of the API resource classes that were added.
 *      Or extend an existing API Resource to add this to.
 *      Or use a vanilla RequestHandlerInterface controller.
 *      @link https://docs.flarum.org/2.x/extend/api#endpoints
 */
class CreateNomineeController extends AbstractCreateController
{
    public $serializer = NomineeSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $relationships = Arr::get($request->getParsedBody(), 'data.relationships', []);

        $categoryId = Arr::get($relationships, 'category.data.id');

        $nominee = Nominee::create([
            'category_id' => $categoryId,
            'name' => Arr::get($data, 'name'),
            'description' => Arr::get($data, 'description'),
            'slug' => Arr::get($data, 'slug') ?: Str::slug(Arr::get($data, 'name')),
            'image_url' => Arr::get($data, 'imageUrl'),
            'metadata' => Arr::get($data, 'metadata'),
            'sort_order' => Arr::get($data, 'sortOrder', 0),
        ]);

        return $nominee;
    }
}
