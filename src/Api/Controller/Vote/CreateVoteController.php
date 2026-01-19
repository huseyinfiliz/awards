<?php

namespace HuseyinFiliz\Awards\Api\Controller\Vote;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\VoteSerializer;
use HuseyinFiliz\Awards\Models\Vote;
use HuseyinFiliz\Awards\Models\Nominee;
use Flarum\Foundation\ValidationException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Illuminate\Cache\RateLimiter;
use HuseyinFiliz\Awards\Service\VoteLimitService;

class CreateVoteController extends AbstractCreateController
{
    public $serializer = VoteSerializer::class;

    protected $translator;
    protected $limiter;
    protected $voteLimitService;

    public function __construct(
        TranslatorInterface $translator,
        RateLimiter $limiter,
        VoteLimitService $voteLimitService
    ) {
        $this->translator = $translator;
        $this->limiter = $limiter;
        $this->voteLimitService = $voteLimitService;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.vote');

        // Rate limit: 10 votes per minute
        $key = 'awards_vote_' . $actor->id;
        if ($this->limiter->tooManyAttempts($key, 10)) {
            throw new ValidationException([
                'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.rate_limit')
            ]);
        }
        $this->limiter->hit($key, 60);

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $nomineeId = Arr::get($data, 'nomineeId');

        $nominee = Nominee::with('category.award')->findOrFail($nomineeId);
        $award = $nominee->category->award;
        $categoryId = $nominee->category_id;

        // Check if voting is open
        if (!$award->isVotingOpen()) {
            throw new ValidationException([
                'message' => $this->translator->trans('huseyinfiliz-awards.forum.voting.voting_closed')
            ]);
        }

        // Single vote mode: replace existing vote
        if ($this->voteLimitService->isSingleVoteMode()) {
            Vote::where('category_id', $categoryId)
                ->where('user_id', $actor->id)
                ->delete();
        } elseif (!$this->voteLimitService->isUnlimited()) {
            // Multi-vote mode with limit: check available slots
            if (!$this->voteLimitService->canVote($categoryId, $actor->id)) {
                $limit = $this->voteLimitService->getVotesPerCategory();
                throw new ValidationException([
                    'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.vote_limit_reached', ['limit' => $limit])
                ]);
            }
        }
        // Unlimited mode: no restrictions

        $vote = Vote::create([
            'nominee_id' => $nomineeId,
            'user_id' => $actor->id,
            'category_id' => $categoryId,
        ]);

        return $vote;
    }
}
