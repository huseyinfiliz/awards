<?php

namespace HuseyinFiliz\Pickem\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use HuseyinFiliz\Pickem\Api\Serializer\SeasonSerializer;
use HuseyinFiliz\Pickem\Season;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListPublicSeasonsController extends AbstractListController
{
    public $serializer = SeasonSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        // Use 'pickem.view' permission instead of 'pickem.manage'
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('pickem.view');

        return Season::orderBy('start_date', 'desc')->get();
    }
}