<?php

namespace HuseyinFiliz\Awards\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use HuseyinFiliz\Awards\Models\Category;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use HuseyinFiliz\Awards\Models\Vote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractDatabaseResource<Category>
 */
class CategoryResource extends Resource\AbstractDatabaseResource
{
    protected static array $userVotesCache = [];
    protected static array $userSuggestionsCache = [];
    protected static ?int $cacheUserId = null;

    public function type(): string
    {
        return 'award-categories';
    }

    public function model(): string
    {
        return Category::class;
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
            Endpoint\Show::make()
                ->defaultInclude(['nominees']),
            Endpoint\Index::make()
                ->paginate()
                ->defaultSort('sortOrder'),
        ];
    }

    protected static function loadUserCache(int $userId): void
    {
        if (static::$cacheUserId !== $userId) {
            static::$userVotesCache = [];
            static::$userSuggestionsCache = [];
            static::$cacheUserId = $userId;
        }

        if (empty(static::$userVotesCache)) {
            $votes = Vote::where('user_id', $userId)
                ->select('category_id', 'nominee_id')
                ->get();

            foreach ($votes as $vote) {
                static::$userVotesCache[$vote->category_id][] = $vote->nominee_id;
            }
        }

        if (empty(static::$userSuggestionsCache)) {
            $suggestions = OtherSuggestion::where('user_id', $userId)
                ->where('status', 'pending')
                ->select('category_id')
                ->get()
                ->groupBy('category_id');

            foreach ($suggestions as $catId => $items) {
                static::$userSuggestionsCache[$catId] = $items->count();
            }
        }
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
            Schema\Integer::make('sortOrder')
                ->writable()
                ->property('sort_order'),
            Schema\Integer::make('totalVotes')
                ->get(fn (Category $model) => $model->total_votes),
            Schema\Integer::make('voteCount')
                ->get(fn (Category $model) => $model->total_votes),
            Schema\Integer::make('nomineeCount')
                ->get(fn (Category $model) => $model->nominee_count),
            Schema\Boolean::make('allowOther')
                ->writable()
                ->property('allow_other'),
            Schema\Integer::make('pendingSuggestionsCount')
                ->get(fn (Category $model) => $model->pendingSuggestions()->count()),
            Schema\Integer::make('userPendingSuggestionsCount')
                ->get(function (Category $model, Context $context) {
                    $actor = $context->getActor();
                    if (!$actor || !$actor->id) {
                        return 0;
                    }
                    static::loadUserCache($actor->id);
                    return static::$userSuggestionsCache[$model->id] ?? 0;
                }),
            Schema\Arr::make('userVoteIds')
                ->get(function (Category $model, Context $context) {
                    $actor = $context->getActor();
                    if (!$actor || !$actor->id) {
                        return [];
                    }
                    static::loadUserCache($actor->id);
                    return static::$userVotesCache[$model->id] ?? [];
                }),

            Schema\Relationship\ToOne::make('award')
                ->includable()
                ->type('awards'),
            Schema\Relationship\ToMany::make('nominees')
                ->includable()
                ->type('award-nominees'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('sortOrder', 'sort_order'),
        ];
    }
}
