<?php

namespace HuseyinFiliz\Awards\Api\Controller\Award;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\AwardSerializer;
use HuseyinFiliz\Awards\Models\Award;

class CreateAwardController extends AbstractCreateController
{
    public $serializer = AwardSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $award = Award::create([
            'name' => Arr::get($data, 'name'),
            'slug' => Arr::get($data, 'slug') ?: Str::slug(Arr::get($data, 'name')),
            'description' => Arr::get($data, 'description'),
            'year' => Arr::get($data, 'year', date('Y')),
            'starts_at' => Arr::get($data, 'startsAt'),
            'ends_at' => Arr::get($data, 'endsAt'),
            'status' => Arr::get($data, 'status', 'draft'),
            'show_live_votes' => Arr::get($data, 'showLiveVotes', false),
            'image_url' => Arr::get($data, 'imageUrl'),
        ]);

        return $award;
    }
}
