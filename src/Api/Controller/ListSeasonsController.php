<?php

namespace HuseyinFiliz\Pickem\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil; // YENİ
use HuseyinFiliz\Pickem\Api\Serializer\SeasonSerializer;
use HuseyinFiliz\Pickem\Season;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListSeasonsController extends AbstractListController
{
    public $serializer = SeasonSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        // YENİ: Admin yetkisi kontrolü eklendi
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('pickem.manage');

        return Season::orderBy('start_date', 'desc')->get();
    }
}