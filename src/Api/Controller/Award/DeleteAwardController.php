<?php

namespace HuseyinFiliz\Awards\Api\Controller\Award;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use HuseyinFiliz\Awards\Models\Award;

class DeleteAwardController extends AbstractDeleteController
{
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $id = Arr::get($request->getQueryParams(), 'id');

        Award::findOrFail($id)->delete();
    }
}
