<?php

namespace HuseyinFiliz\Awards\Api\Controller\Award;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\AwardSerializer;
use HuseyinFiliz\Awards\Models\Award;
use Flarum\User\Exception\PermissionDeniedException;

/**
 * @TODO: Remove this in favor of one of the API resource classes that were added.
 *      Or extend an existing API Resource to add this to.
 *      Or use a vanilla RequestHandlerInterface controller.
 *      @link https://docs.flarum.org/2.x/extend/api#endpoints
 */
class ShowAwardController extends AbstractShowController
{
    public $serializer = AwardSerializer::class;

    public $include = ['categories', 'categories.nominees'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.view');

        $id = Arr::get($request->getQueryParams(), 'id');

        $award = Award::with($this->extractInclude($request))->findOrFail($id);

        if (!$actor->can('awards.manage') && $award->isDraft()) {
             throw new PermissionDeniedException();
        }

        return $award;
    }
}
