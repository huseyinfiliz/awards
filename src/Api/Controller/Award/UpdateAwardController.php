<?php

namespace HuseyinFiliz\Awards\Api\Controller\Award;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\AwardSerializer;
use HuseyinFiliz\Awards\Models\Award;

class UpdateAwardController extends AbstractShowController
{
    public $serializer = AwardSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $award = Award::findOrFail($id);

        if (Arr::has($data, 'name')) {
            $award->name = Arr::get($data, 'name');
        }
        if (Arr::has($data, 'slug')) {
            $award->slug = Arr::get($data, 'slug') ?: Str::slug($award->name);
        }
        if (Arr::has($data, 'description')) {
            $award->description = Arr::get($data, 'description');
        }
        if (Arr::has($data, 'year')) {
            $award->year = Arr::get($data, 'year');
        }
        if (Arr::has($data, 'startsAt')) {
            $award->starts_at = Arr::get($data, 'startsAt');
        }
        if (Arr::has($data, 'endsAt')) {
            $award->ends_at = Arr::get($data, 'endsAt');
        }
        if (Arr::has($data, 'status')) {
            $award->status = Arr::get($data, 'status');
        }
        if (Arr::has($data, 'showLiveVotes')) {
            $award->show_live_votes = Arr::get($data, 'showLiveVotes');
        }
        if (Arr::has($data, 'imageUrl')) {
            $award->image_url = Arr::get($data, 'imageUrl');
        }

        $award->save();

        return $award;
    }
}
