<?php

use Flarum\Extend;
use HuseyinFiliz\Awards\Api\Controller;
use HuseyinFiliz\Awards\Notification;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/resources/less/forum.less')
        ->route('/awards', 'awards', \HuseyinFiliz\Awards\Forum\Controller\AwardsController::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js')
        ->css(__DIR__ . '/resources/less/admin.less'),

    new Extend\Locales(__DIR__ . '/resources/locale'),

    (new Extend\Settings())
        ->default('huseyinfiliz-awards.allow_guest_view', false)
        ->default('huseyinfiliz-awards.votes_per_category', 1)
        ->serializeToForum('awardsVotesPerCategory', 'huseyinfiliz-awards.votes_per_category'),

    (new Extend\Notification())
        ->type(Notification\ResultsPublishedBlueprint::class, \HuseyinFiliz\Awards\Api\Serializer\AwardSerializer::class, ['alert', 'email']),

    (new Extend\Routes('api'))
        // Awards
        ->get('/awards', 'awards.index', Controller\Award\ListAwardsController::class)
        ->post('/awards', 'awards.create', Controller\Award\CreateAwardController::class)
        ->get('/awards/{id}', 'awards.show', Controller\Award\ShowAwardController::class)
        ->patch('/awards/{id}', 'awards.update', Controller\Award\UpdateAwardController::class)
        ->delete('/awards/{id}', 'awards.delete', Controller\Award\DeleteAwardController::class)
        ->post('/awards/{id}/publish', 'awards.publish', Controller\Admin\PublishResultsController::class)

        // Categories - autocomplete ÖNCE gelmeli
        ->get('/award-categories/autocomplete', 'award-categories.autocomplete', Controller\Category\AutocompleteCategoriesController::class)
        ->get('/award-categories', 'award-categories.index', Controller\Category\ListCategoriesController::class)
        ->post('/award-categories', 'award-categories.create', Controller\Category\CreateCategoryController::class)
        ->get('/award-categories/{id}', 'award-categories.show', Controller\Category\ShowCategoryController::class)
        ->patch('/award-categories/{id}', 'award-categories.update', Controller\Category\UpdateCategoryController::class)
        ->delete('/award-categories/{id}', 'award-categories.delete', Controller\Category\DeleteCategoryController::class)

        // Nominees - autocomplete ÖNCE gelmeli
        ->get('/award-nominees/autocomplete', 'award-nominees.autocomplete', Controller\Nominee\AutocompleteNomineesController::class)
        ->get('/award-nominees', 'award-nominees.index', Controller\Nominee\ListNomineesController::class)
        ->post('/award-nominees', 'award-nominees.create', Controller\Nominee\CreateNomineeController::class)
        ->get('/award-nominees/{id}', 'award-nominees.show', Controller\Nominee\ShowNomineeController::class)
        ->patch('/award-nominees/{id}', 'award-nominees.update', Controller\Nominee\UpdateNomineeController::class)
        ->delete('/award-nominees/{id}', 'award-nominees.delete', Controller\Nominee\DeleteNomineeController::class)
        ->patch('/award-nominees/{id}/votes', 'award-nominees.updateVotes', Controller\Admin\UpdateNomineeVotesController::class)

        // Votes
        ->get('/award-votes', 'award-votes.index', Controller\Vote\ListVotesController::class)
        ->post('/award-votes', 'award-votes.create', Controller\Vote\CreateVoteController::class)
        ->delete('/award-votes/{id}', 'award-votes.delete', Controller\Vote\DeleteVoteController::class)

        // Other Suggestions
        ->get('/award-other-suggestions', 'award-other-suggestions.index', Controller\OtherSuggestion\ListPendingSuggestionsController::class)
        ->post('/award-other-suggestions', 'award-other-suggestions.create', Controller\OtherSuggestion\CreateOtherSuggestionController::class)
        ->patch('/award-other-suggestions/{id}', 'award-other-suggestions.update', Controller\OtherSuggestion\UpdateOtherSuggestionController::class),

    (new Extend\Policy())
        ->modelPolicy(\HuseyinFiliz\Awards\Models\Award::class, \HuseyinFiliz\Awards\Access\AwardPolicy::class),
];