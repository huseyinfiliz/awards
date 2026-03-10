<?php

namespace HuseyinFiliz\Awards\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use HuseyinFiliz\Awards\Models\Nominee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractDatabaseResource<Nominee>
 */
class NomineeResource extends Resource\AbstractDatabaseResource
{
    public function type(): string
    {
        return 'award-nominees';
    }

    public function model(): string
    {
        return Nominee::class;
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
                    $context->getActor()->assertCan('awards.manage');
                    $attrs = (array) ($context->body()['data']['attributes'] ?? []);

                    if (empty($attrs['slug'] ?? '')) {
                        $context->body = array_merge($context->body(), [
                            'data' => array_merge($context->body()['data'] ?? [], [
                                'attributes' => array_merge($attrs, [
                                    'slug' => Str::slug($attrs['name'] ?? ''),
                                ]),
                            ]),
                        ]);
                    }
                }),
            Endpoint\Update::make()
                ->can('update')
                ->before(function (OriginalContext $context) {
                    $context->getActor()->assertCan('awards.manage');
                    $attrs = (array) ($context->body()['data']['attributes'] ?? []);

                    if (array_key_exists('slug', $attrs) && empty($attrs['slug'])) {
                        $model = $context->model;
                        $context->body = array_merge($context->body(), [
                            'data' => array_merge($context->body()['data'] ?? [], [
                                'attributes' => array_merge($attrs, [
                                    'slug' => Str::slug($model->name),
                                ]),
                            ]),
                        ]);
                    }
                }),
            Endpoint\Delete::make()
                ->can('delete')
                ->before(function (OriginalContext $context) {
                    $context->getActor()->assertCan('awards.manage');
                }),
            Endpoint\Show::make(),
            Endpoint\Index::make()
                ->paginate()
                ->defaultSort('sortOrder'),
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
            Schema\Str::make('description')
                ->writable(),
            Schema\Str::make('slug')
                ->writable(),
            Schema\Str::make('imageUrl')
                ->writable()
                ->property('image_url'),
            Schema\Arr::make('metadata')
                ->writable(),
            Schema\Integer::make('sortOrder')
                ->writable()
                ->property('sort_order'),
            Schema\Integer::make('voteCount')
                ->get(function (Nominee $model, Context $context) {
                    $award = $model->category->award;
                    if ($context->getActor()->hasPermission('awards.manage')) {
                        return $model->vote_count;
                    }
                    if ($award && $award->canShowVotes()) {
                        return $model->vote_count;
                    }
                    return null;
                }),
            Schema\Number::make('votePercentage')
                ->get(function (Nominee $model, Context $context) {
                    $award = $model->category->award;
                    if ($context->getActor()->hasPermission('awards.manage')) {
                        return $model->vote_percentage;
                    }
                    if ($award && $award->canShowVotes()) {
                        return $model->vote_percentage;
                    }
                    return null;
                }),
            Schema\Integer::make('realVoteCount')
                ->visible(fn (Nominee $model, Context $context) => $context->getActor()->hasPermission('awards.manage'))
                ->get(fn (Nominee $model) => $model->real_vote_count),
            Schema\Integer::make('voteAdjustment')
                ->visible(fn (Nominee $model, Context $context) => $context->getActor()->hasPermission('awards.manage'))
                ->get(fn (Nominee $model) => $model->vote_adjustment ?? 0),

            Schema\Relationship\ToOne::make('category')
                ->includable()
                ->type('award-categories'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('sortOrder', 'sort_order'),
        ];
    }
}
