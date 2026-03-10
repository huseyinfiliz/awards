<?php

namespace HuseyinFiliz\Awards\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Foundation\ValidationException;
use HuseyinFiliz\Awards\Models\Category;
use HuseyinFiliz\Awards\Models\Nominee;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use HuseyinFiliz\Awards\Models\Vote;
use HuseyinFiliz\Awards\Service\VoteLimitService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractDatabaseResource<OtherSuggestion>
 */
class OtherSuggestionResource extends Resource\AbstractDatabaseResource
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected RateLimiter $limiter,
        protected VoteLimitService $voteLimitService,
    ) {
    }

    public function type(): string
    {
        return 'award-other-suggestions';
    }

    public function model(): string
    {
        return OtherSuggestion::class;
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
                    $name = trim($attrs['name'] ?? '');
                    $categoryId = $attrs['categoryId'] ?? null;

                    if (empty($name)) {
                        throw new ValidationException([
                            'name' => $this->translator->trans('validation.required', ['attribute' => 'name'])
                        ]);
                    }

                    if (mb_strlen($name) > 255) {
                        throw new ValidationException([
                            'name' => $this->translator->trans('validation.max.string', ['attribute' => 'name', 'max' => 255])
                        ]);
                    }

                    $category = Category::with('award')->findOrFail($categoryId);

                    if (!$category->allow_other) {
                        throw new ValidationException([
                            'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.other_not_allowed')
                        ]);
                    }

                    if (!$category->award->isVotingOpen()) {
                        throw new ValidationException([
                            'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.voting_closed')
                        ]);
                    }

                    if (!$this->voteLimitService->isUnlimited()) {
                        if (!$this->voteLimitService->canVote($categoryId, $actor->id)) {
                            throw new ValidationException([
                                'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.vote_quota_exhausted')
                            ]);
                        }
                    }

                    $context->body = array_merge($context->body(), [
                        'data' => array_merge($context->body()['data'] ?? [], [
                            'attributes' => array_merge($attrs, [
                                'name' => $name,
                                'status' => 'pending',
                            ]),
                        ]),
                    ]);
                }),
            Endpoint\Update::make()
                ->can('update')
                ->before(function (OriginalContext $context) {
                    $context->getActor()->assertCan('awards.manage');
                    $attrs = (array) ($context->body()['data']['attributes'] ?? []);
                    $action = $attrs['action'] ?? null;
                    $mergeToNomineeId = $attrs['mergeToNomineeId'] ?? null;
                    $model = $context->model;

                    switch ($action) {
                        case 'approve':
                            $nominee = Nominee::create([
                                'category_id' => $model->category_id,
                                'name' => $model->name,
                                'slug' => Str::slug($model->name),
                                'sort_order' => 999,
                            ]);

                            Vote::updateOrCreate(
                                ['category_id' => $model->category_id, 'user_id' => $model->user_id],
                                ['nominee_id' => $nominee->id]
                            );

                            $context->body = array_merge($context->body(), [
                                'data' => array_merge($context->body()['data'] ?? [], [
                                    'attributes' => array_merge($attrs, [
                                        'status' => 'approved',
                                        'mergedToNomineeId' => $nominee->id,
                                    ]),
                                ]),
                            ]);
                            break;

                        case 'reject':
                            $context->body = array_merge($context->body(), [
                                'data' => array_merge($context->body()['data'] ?? [], [
                                    'attributes' => array_merge($attrs, [
                                        'status' => 'rejected',
                                    ]),
                                ]),
                            ]);
                            break;

                        case 'merge':
                            if (!$mergeToNomineeId) {
                                throw new \InvalidArgumentException('mergeToNomineeId is required for merge action');
                            }

                            Vote::updateOrCreate(
                                ['category_id' => $model->category_id, 'user_id' => $model->user_id],
                                ['nominee_id' => $mergeToNomineeId]
                            );

                            $context->body = array_merge($context->body(), [
                                'data' => array_merge($context->body()['data'] ?? [], [
                                    'attributes' => array_merge($attrs, [
                                        'status' => 'merged',
                                        'mergedToNomineeId' => $mergeToNomineeId,
                                    ]),
                                ]),
                            ]);
                            break;
                    }
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
            Schema\Str::make('name')
                ->requiredOnCreate()
                ->maxLength(255)
                ->writable(),
            Schema\Str::make('status')
                ->writable(),
            Schema\DateTime::make('createdAt')
                ->property('created_at'),
            Schema\Integer::make('categoryId')
                ->writable()
                ->property('category_id'),
            Schema\Integer::make('mergedToNomineeId')
                ->writable()
                ->property('merged_to_nominee_id'),

            Schema\Relationship\ToOne::make('category')
                ->includable()
                ->type('award-categories'),
            Schema\Relationship\ToOne::make('user')
                ->includable()
                ->type('users'),
            Schema\Relationship\ToOne::make('mergedToNominee')
                ->includable()
                ->type('award-nominees'),
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
