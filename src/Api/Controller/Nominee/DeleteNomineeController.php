<?php

namespace HuseyinFiliz\Awards\Api\Controller\Nominee;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use HuseyinFiliz\Awards\Models\Nominee;

class DeleteNomineeController extends AbstractDeleteController
{
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $id = Arr::get($request->getQueryParams(), 'id');

        Nominee::findOrFail($id)->delete();
    }
}
