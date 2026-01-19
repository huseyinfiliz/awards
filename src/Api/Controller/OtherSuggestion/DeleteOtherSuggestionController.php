<?php

namespace HuseyinFiliz\Awards\Api\Controller\OtherSuggestion;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use Flarum\Foundation\ValidationException;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteOtherSuggestionController extends AbstractDeleteController
{
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    protected function delete(ServerRequestInterface $request)
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
