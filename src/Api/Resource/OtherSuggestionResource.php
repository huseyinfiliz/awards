<?php

namespace HuseyinFiliz\Awards\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Foundation\ValidationException;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use HuseyinFiliz\Awards\Service\VotingService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Database\Eloquent\Builder;
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
        protected VotingService $voting,
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
                    $key = 'awards_vote_'.$actor->id;
                    if ($this->limiter->tooManyAttempts($key, 10)) {
                        throw new ValidationException([
                            'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.rate_limit'),
                        ]);
                    }
                    $this->limiter->hit($key, 60);
                }),
            Endpoint\Update::make()
                ->can('update')
                ->before(fn (OriginalContext $context) => $context->getActor()->assertCan('awards.manage')),
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
                ->minLength(1)
                ->maxLength(255)
                ->writableOnCreate(),
            Schema\Str::make('status'),
            Schema\Str::make('action')
                ->hidden()
                ->writableOnUpdate()
                ->set(fn () => null),
            Schema\DateTime::make('createdAt')
                ->property('created_at'),
            Schema\Integer::make('categoryId')
                ->requiredOnCreate()
                ->writableOnCreate()
                ->property('category_id'),
            Schema\Integer::make('mergedToNomineeId')
                ->property('merged_to_nominee_id'),
            Schema\Integer::make('mergeToNomineeId')
                ->hidden()
                ->writableOnUpdate()
                ->set(fn () => null),
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

    public function setValue(object $model, \Tobyz\JsonApiServer\Schema\Field\Field $field, mixed $value, OriginalContext $context): void
    {
        if ($field->name === 'name') {
            $value = trim($value);
            if ($value === '') {
                throw new ValidationException(['name' => $this->translator->trans('validation.required', ['attribute' => 'name'])]);
            }
        }
        parent::setValue($model, $field, $value, $context);
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
        return $this->voting->suggest($model);
    }

    public function update(object $model, OriginalContext $context): object
    {
        $attrs = $context->body()['data']['attributes'] ?? [];

        return $this->voting->moderate($model, $attrs['action'] ?? '', isset($attrs['mergeToNomineeId']) ? (int) $attrs['mergeToNomineeId'] : null);
    }

    public function delete(object $model, OriginalContext $context): void
    {
        $this->voting->delete($model);
    }
}
