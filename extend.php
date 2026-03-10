<?php

use Flarum\Extend;
use Flarum\Api\Resource\ForumResource;
use Flarum\Api\Schema;
use HuseyinFiliz\Awards\Api\Controller;
use HuseyinFiliz\Awards\Notification;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/resources/less/forum.less')
        ->route('/awards', 'awards', \HuseyinFiliz\Awards\Forum\Controller\AwardsController::class)
        ->route('/awards/{id:[0-9]+}-{slug}', 'awards.show', \HuseyinFiliz\Awards\Forum\Controller\AwardsController::class)
        ->route('/awards/{id:[0-9]+}-{slug}/{category:[0-9]+}', 'awards.category', \HuseyinFiliz\Awards\Forum\Controller\AwardsController::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js')
        ->css(__DIR__ . '/resources/less/admin.less'),

    new Extend\Locales(__DIR__ . '/resources/locale'),

    (new Extend\Settings())
        ->default('huseyinfiliz-awards.votes_per_category', 1)
        ->default('huseyinfiliz-awards.nav_title', 'Awards')
        ->default('huseyinfiliz-awards.nav_icon', 'fas fa-trophy')
        ->serializeToForum('awardsVotesPerCategory', 'huseyinfiliz-awards.votes_per_category')
        ->serializeToForum('awardsNavTitle', 'huseyinfiliz-awards.nav_title')
        ->serializeToForum('awardsNavIcon', 'huseyinfiliz-awards.nav_icon'),

    (new Extend\ApiResource(ForumResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canViewAwards')
                ->get(fn ($model, $context) => $context->getActor()->hasPermission('awards.view')),
            Schema\Boolean::make('canVoteAwards')
                ->get(fn ($model, $context) => $context->getActor()->hasPermission('awards.vote')),
            Schema\Boolean::make('canViewAwardsResults')
                ->get(fn ($model, $context) => $context->getActor()->hasPermission('awards.viewResults')),
            Schema\Boolean::make('canManageAwards')
                ->get(fn ($model, $context) => $context->getActor()->hasPermission('awards.manage')),
        ]),

    (new Extend\Notification())
        ->type(Notification\ResultsPublishedBlueprint::class, ['alert']),

    (new Extend\Routes('api'))
        ->post('/awards/{id}/publish', 'awards.publish', Controller\Admin\PublishResultsController::class)
        ->get('/award-category-autocomplete', 'award-categories.autocomplete', Controller\Category\AutocompleteCategoriesController::class)
        ->get('/award-nominee-autocomplete', 'award-nominees.autocomplete', Controller\Nominee\AutocompleteNomineesController::class)
        ->patch('/award-nominees/{id}/votes', 'award-nominees.updateVotes', Controller\Admin\UpdateNomineeVotesController::class)
        ->get('/award-user-suggestions', 'award-other-suggestions.mine', Controller\OtherSuggestion\ListUserSuggestionsController::class),

    (new Extend\Policy())
        ->modelPolicy(\HuseyinFiliz\Awards\Models\Award::class, \HuseyinFiliz\Awards\Access\AwardPolicy::class)
        ->modelPolicy(\HuseyinFiliz\Awards\Models\Category::class, \HuseyinFiliz\Awards\Access\CategoryPolicy::class)
        ->modelPolicy(\HuseyinFiliz\Awards\Models\Nominee::class, \HuseyinFiliz\Awards\Access\NomineePolicy::class)
        ->modelPolicy(\HuseyinFiliz\Awards\Models\Vote::class, \HuseyinFiliz\Awards\Access\VotePolicy::class)
        ->modelPolicy(\HuseyinFiliz\Awards\Models\OtherSuggestion::class, \HuseyinFiliz\Awards\Access\OtherSuggestionPolicy::class),

    (new Extend\ModelVisibility(\HuseyinFiliz\Awards\Models\Award::class))
        ->scope(function ($actor, $query) {
            if (!$actor->hasPermission('awards.manage')) {
                $query->whereIn('status', ['active', 'published', 'ended']);
            }
        }),

    (new Extend\ModelVisibility(\HuseyinFiliz\Awards\Models\Vote::class))
        ->scope(function ($actor, $query) {
            if (!$actor->hasPermission('awards.manage')) {
                $query->where('user_id', $actor->id);
            }
        }),

    (new Extend\ModelVisibility(\HuseyinFiliz\Awards\Models\OtherSuggestion::class))
        ->scope(function ($actor, $query) {
            if (!$actor->hasPermission('awards.manage')) {
                $query->where('user_id', $actor->id);
            }
        }),

    new Extend\ApiResource(HuseyinFiliz\Awards\Api\Resource\AwardResource::class),
    new Extend\ApiResource(HuseyinFiliz\Awards\Api\Resource\CategoryResource::class),
    new Extend\ApiResource(HuseyinFiliz\Awards\Api\Resource\NomineeResource::class),
    new Extend\ApiResource(HuseyinFiliz\Awards\Api\Resource\OtherSuggestionResource::class),
    new Extend\ApiResource(HuseyinFiliz\Awards\Api\Resource\VoteResource::class),
];
