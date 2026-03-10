<?php

namespace HuseyinFiliz\Awards\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Foundation\ValidationException;
use HuseyinFiliz\Awards\Models\Nominee;
use HuseyinFiliz\Awards\Models\Vote;
use HuseyinFiliz\Awards\Service\VoteLimitService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractDatabaseResource<Vote>
 */
class VoteResource extends Resource\AbstractDatabaseResource
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected RateLimiter $limiter,
        protected VoteLimitService $voteLimitService,
    ) {
    }

    public function type(): string
    {
        return 'award-votes';
    }

    public function model(): string
    {
        return Vote::class;
    }

    public function scope(Builder $query, OriginalContext $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->before(function (OriginalContext $context) {
                    $actor = $context->getActor();
                    $actor->assertCan('awards.vote');

                    $key = 'awards_vote_' . $actor->id;
                    if ($this->limiter->tooManyAttempts($key, 10)) {
                        throw new ValidationException([
                            'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.rate_limit')
                        ]);
                    }
                    $this->limiter->hit($key, 60);

                    $attrs = (array) ($context->body()['data']['attributes'] ?? []);
                    $nomineeId = $attrs['nomineeId'] ?? null;

                    $nominee = Nominee::with('category.award')->findOrFail($nomineeId);
                    $award = $nominee->category->award;
                    $categoryId = $nominee->category_id;

                    if (!$award->isVotingOpen()) {
                        throw new ValidationException([
                            'message' => $this->translator->trans('huseyinfiliz-awards.forum.voting.voting_closed')
                        ]);
                    }

                    if ($this->voteLimitService->isSingleVoteMode()) {
                        Vote::where('category_id', $categoryId)
                            ->where('user_id', $actor->id)
                            ->delete();
                    } elseif (!$this->voteLimitService->isUnlimited()) {
                        if (!$this->voteLimitService->canVote($categoryId, $actor->id)) {
                            $limit = $this->voteLimitService->getVotesPerCategory();
                            throw new ValidationException([
                                'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.vote_limit_reached', ['limit' => $limit])
                            ]);
                        }
                    }

                    $context->body = array_merge($context->body(), [
                        'data' => array_merge($context->body()['data'] ?? [], [
                            'attributes' => array_merge($attrs, [
                                'categoryId' => $categoryId,
                            ]),
                        ]),
                    ]);
                }),
            Endpoint\Delete::make()
                ->can('delete'),
            Endpoint\Index::make()
                ->paginate(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\DateTime::make('createdAt')
                ->property('created_at'),
            Schema\Integer::make('nomineeId')
                ->writable()
                ->requiredOnCreate()
                ->property('nominee_id'),
            Schema\Integer::make('categoryId')
                ->writable()
                ->property('category_id'),

            Schema\Relationship\ToOne::make('nominee')
                ->includable()
                ->type('award-nominees'),
            Schema\Relationship\ToOne::make('category')
                ->includable()
                ->type('award-categories'),
            Schema\Relationship\ToOne::make('user')
                ->includable()
                ->type('users'),
        ];
    }

    public function sorts(): array
    {
        return [];
    }

    public function newModel(OriginalContext $context): object
    {
        $model = parent::newModel($context);
        $model->user_id = $context->getActor()->id;
        return $model;
    }
}
