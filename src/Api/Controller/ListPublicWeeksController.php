<?php

namespace HuseyinFiliz\Pickem\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use HuseyinFiliz\Pickem\Api\Serializer\WeekSerializer;
use HuseyinFiliz\Pickem\Week;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListPublicWeeksController extends AbstractListController
{
    public $serializer = WeekSerializer::class;
    
    // Public olduğu için 'season' ilişkisini dahil etmeye gerek yok
    // public $include = ['season']; 

    protected function data(ServerRequestInterface $request, Document $document)
    {
        // Use 'pickem.view' permission instead of 'pickem.manage'
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('pickem.view');

        $query = Week::query();

        // Admin panelinin aksine, burada SADECE sezon ID'si olan
        // haftaları listelemek daha mantıklı olabilir.
        // $query->whereNotNull('season_id');

        return $query->get();
    }
}