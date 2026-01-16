<?php

namespace HuseyinFiliz\Awards\Api\Controller\Award;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\AwardSerializer;
use HuseyinFiliz\Awards\Models\Award;

class ListAwardsController extends AbstractListController
{
    public $serializer = AwardSerializer::class;

    public $include = ['categories', 'categories.nominees'];

    public $optionalInclude = ['categories', 'categories.nominees'];

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.view');

        $query = Award::withCount('categories')
            ->orderBy('year', 'desc')
            ->orderBy('created_at', 'desc');

        // Non-admins only see active or published
        if (!$actor->can('awards.manage')) {
            $query->whereIn('status', ['active', 'published', 'ended']);
        }

        return $query->get();
    }
}
