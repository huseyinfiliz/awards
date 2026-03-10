<?php

namespace HuseyinFiliz\Awards\Api\Controller\OtherSuggestion;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use Flarum\Foundation\ValidationException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @TODO: Remove this in favor of one of the API resource classes that were added.
 *      Or extend an existing API Resource to add this to.
 *      Or use a vanilla RequestHandlerInterface controller.
 *      @link https://docs.flarum.org/2.x/extend/api#endpoints
 */
class DeleteOtherSuggestionController extends AbstractDeleteController
{
    public function __construct(protected TranslatorInterface $translator)
    {
    }

    protected function delete(ServerRequestInterface $request): void
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        $suggestion = OtherSuggestion::findOrFail($id);

        // User can only delete their own suggestions
        if ($suggestion->user_id !== $actor->id) {
            // Unless they have manage permission
            $actor->assertCan('awards.manage');
        }

        // Only pending suggestions can be deleted by users
        if ($suggestion->status !== 'pending' && !$actor->hasPermission('awards.manage')) {
            throw new ValidationException([
                'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.cannot_delete_processed_suggestion')
            ]);
        }

        $suggestion->delete();
    }
}
