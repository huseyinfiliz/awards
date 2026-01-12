<?php

namespace HuseyinFiliz\Pickem\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil; // YENİ: Import et
use HuseyinFiliz\Pickem\Api\Serializer\TeamSerializer;
use HuseyinFiliz\Pickem\Team;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListTeamsController extends AbstractListController
{
    public $serializer = TeamSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        // YENİ: Admin yetkisi kontrolü eklendi
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('pickem.manage');

        return Team::all();
    }
}