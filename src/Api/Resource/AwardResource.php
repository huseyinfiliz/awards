<?php

namespace HuseyinFiliz\Awards\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use HuseyinFiliz\Awards\Models\Award;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractDatabaseResource<Award>
 */
class AwardResource extends Resource\AbstractDatabaseResource
{
    public function type(): string
    {
        return 'awards';
    }

    public function model(): string
    {
        return Award::class;
    }

    public function scope(Builder $query, OriginalContext $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->can('createAward')
                ->defaultInclude(['categories'])
                ->before(function (OriginalContext $context) {
                    $context->getActor()->assertCan('awards.manage');
                    $attrs = (array) $context->body()['data']['attributes'] ?? [];

                    $name = $attrs['name'] ?? '';
                    $year = $attrs['year'] ?? date('Y');

                    $baseSlug = ($attrs['slug'] ?? '') ?: Str::slug($name) . '-' . $year;
                    $slug = $baseSlug;
                    $counter = 2;

                    while (Award::where('slug', $slug)->exists()) {
                        $slug = $baseSlug . '-' . $counter;
                        $counter++;
                    }

                    $context->body = array_merge($context->body(), [
                        'data' => array_merge($context->body()['data'] ?? [], [
                            'attributes' => array_merge($attrs, ['slug' => $slug]),
                        ]),
                    ]);
                }),
            Endpoint\Update::make()
                ->can('update')
                ->before(function (OriginalContext $context) {
                    $context->getActor()->assertCan('awards.manage');
                    $attrs = (array) ($context->body()['data']['attributes'] ?? []);

                    if (array_key_exists('slug', $attrs)) {
                        $model = $context->model;
                        $providedSlug = $attrs['slug'];
                        $baseSlug = $providedSlug ?: Str::slug($model->name) . '-' . $model->year;
                        $slug = $baseSlug;
                        $counter = 2;

                        while (Award::where('slug', $slug)->where('id', '!=', $model->id)->exists()) {
                            $slug = $baseSlug . '-' . $counter;
                            $counter++;
                        }

                        $context->body = array_merge($context->body(), [
                            'data' => array_merge($context->body()['data'] ?? [], [
                                'attributes' => array_merge($attrs, ['slug' => $slug]),
                            ]),
                        ]);
                    }
                }),
            Endpoint\Delete::make()
                ->can('delete'),
            Endpoint\Show::make()
                ->defaultInclude(['categories', 'categories.nominees'])
                ->before(function (OriginalContext $context) {
                    $context->getActor()->assertCan('awards.view');
                }),
            Endpoint\Index::make()
                ->paginate()
                ->defaultInclude(['categories'])
                ->defaultSort('-startsAt')
                ->before(function (OriginalContext $context) {
                    $context->getActor()->assertCan('awards.view');
                }),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('name')
                ->requiredOnCreate()
                ->minLength(1)
                ->maxLength(255)
                ->writable(),
            Schema\Str::make('slug')
                ->writable(),
            Schema\Str::make('description')
                ->writable(),
            Schema\Integer::make('year')
                ->writable()
                ->set(function (Award $model, int $value) {
                    $model->year = $value;
                }),
            Schema\DateTime::make('startsAt')
                ->writable()
                ->property('starts_at'),
            Schema\DateTime::make('endsAt')
                ->writable()
                ->property('ends_at'),
            Schema\Str::make('status')
                ->writable()
                ->set(function (Award $model, string $value) {
                    $model->status = $value;
                }),
            Schema\Str::make('effectiveStatus')
                ->get(fn (Award $model) => $model->getEffectiveStatus()),
            Schema\Boolean::make('showLiveVotes')
                ->writable()
                ->property('show_live_votes'),
            Schema\Str::make('imageUrl')
                ->writable()
                ->property('image_url'),
            Schema\Boolean::make('isDraft')
                ->get(fn (Award $model) => $model->isDraft()),
            Schema\Boolean::make('isActive')
                ->get(fn (Award $model) => $model->isActive()),
            Schema\Boolean::make('hasEnded')
                ->get(fn (Award $model) => $model->hasEnded()),
            Schema\Boolean::make('isPublished')
                ->get(fn (Award $model) => $model->isPublished()),
            Schema\Boolean::make('isVotingOpen')
                ->get(fn (Award $model) => $model->isVotingOpen()),
            Schema\Boolean::make('canShowVotes')
                ->get(fn (Award $model) => $model->canShowVotes()),
            Schema\Boolean::make('canViewResults')
                ->get(function (Award $model, Context $context) {
                    $actor = $context->getActor();
                    return $model->isPublished()
                        || ($model->hasEnded() && $actor->hasPermission('awards.viewResults'));
                }),
            Schema\Integer::make('categoryCount')
                ->get(fn (Award $model) => $model->category_count),
            Schema\Integer::make('nomineeCount')
                ->get(fn (Award $model) => $model->nominee_count),
            Schema\Integer::make('voteCount')
                ->get(fn (Award $model) => $model->vote_count),

            Schema\Relationship\ToMany::make('categories')
                ->includable()
                ->type('award-categories'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('startsAt', 'starts_at'),
            SortColumn::make('year'),
        ];
    }
}
