<?php

namespace HuseyinFiliz\Awards\Api\Controller\Vote;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use HuseyinFiliz\Awards\Models\Vote;
use Symfony\Contracts\Translation\TranslatorInterface;
use Flarum\Foundation\ValidationException;

class DeleteVoteController extends AbstractDeleteController
{
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.vote');

        $id = Arr::get($request->getQueryParams(), 'id');

        $vote = Vote::where('id', $id)->where('user_id', $actor->id)->firstOrFail();

        $award = $vote->category->award;

        if (!$award->isVotingOpen()) {
             throw new ValidationException([
                'message' => $this->translator->trans('huseyinfiliz-awards.forum.voting.voting_closed')
            ]);
        }

        $vote->delete();
    }
}
