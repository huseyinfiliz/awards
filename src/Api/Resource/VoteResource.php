<?php

namespace HuseyinFiliz\Awards\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Foundation\ValidationException;
use HuseyinFiliz\Awards\Models\Vote;
use HuseyinFiliz\Awards\Service\VotingService;
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
        protected VotingService $voting,
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
        $actor = $context->getActor();
        $query->whereVisibleTo($actor);

        // By default on list queries, users only see their own votes
        if ($context->request->getMethod() === 'GET' && !str_contains($context->path(), '/') && !isset($context->request->getQueryParams()['all'])) {
            $query->where('user_id', $actor->id);
        }
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->before(function (OriginalContext $context) {
                    $actor = $context->getActor();
                    $actor->assertCan('awards.vote');

                    $key = 'awards_vote_'.$actor->id;
                    if ($this->limiter->tooManyAttempts($key, 10)) {
                        throw new ValidationException([
                            'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.rate_limit'),
                        ]);
                    }
                    $this->limiter->hit($key, 60);
                }),
            Endpoint\Delete::make()
                ->can('delete'),
            Endpoint\Index::make()
                ->paginate(defaultLimit: 100, maxLimit: 500),
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
            Schema\Integer::make('userId')
                ->property('user_id'),

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

    public function create(object $model, OriginalContext $context): object
    {
        return $this->voting->vote($model);
    }

    public function delete(object $model, OriginalContext $context): void
    {
        $this->voting->delete($model);
    }
}
