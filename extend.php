<?php

namespace HuseyinFiliz\Pickem;

use Flarum\Extend;
use Flarum\User\User;
use HuseyinFiliz\Pickem\Api\Controller;
use HuseyinFiliz\Pickem\Api\Serializer;
use Flarum\Api\Serializer\ForumSerializer;
use HuseyinFiliz\Pickem\Access\EventPolicy;
use HuseyinFiliz\Pickem\Access\PickPolicy;
use HuseyinFiliz\Pickem\Event;
use HuseyinFiliz\Pickem\Pick;
use HuseyinFiliz\Pickem\UserScore;
use HuseyinFiliz\Pickem\Listener;
use HuseyinFiliz\Pickem\Notification;

return [
    // Frontend Assets
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less')
        ->route('/pickem', 'pickem'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less'),

    // Localization
    new Extend\Locales(__DIR__.'/resources/locale'),

    // API Routes
    (new Extend\Routes('api'))
        // Teams (Admin)
        ->get('/pickem-teams', 'pickem.teams.index', Controller\ListTeamsController::class)
        ->post('/pickem-teams', 'pickem.teams.create', Controller\CreateTeamController::class)
        ->patch('/pickem-teams/{id}', 'pickem.teams.update', Controller\UpdateTeamController::class)
        ->delete('/pickem-teams/{id}', 'pickem.teams.delete', Controller\DeleteTeamController::class)
        
        // Seasons (Admin)
        ->get('/pickem-seasons', 'pickem.seasons.index', Controller\ListSeasonsController::class)
        ->post('/pickem-seasons', 'pickem.seasons.create', Controller\CreateSeasonController::class)
        ->patch('/pickem-seasons/{id}', 'pickem.seasons.update', Controller\UpdateSeasonController::class)
        ->delete('/pickem-seasons/{id}', 'pickem.seasons.delete', Controller\DeleteSeasonController::class)
        
        // Weeks (Admin)
        ->get('/pickem-weeks', 'pickem.weeks.index', Controller\ListWeeksController::class)
        ->post('/pickem-weeks', 'pickem.weeks.create', Controller\CreateWeekController::class)
        ->patch('/pickem-weeks/{id}', 'pickem.weeks.update', Controller\UpdateWeekController::class)
        ->delete('/pickem-weeks/{id}', 'pickem.weeks.delete', Controller\DeleteWeekController::class)
        
        // Events (Matches)
        ->get('/pickem-events', 'pickem.events.index', Controller\ListEventsController::class)
        ->post('/pickem-events', 'pickem.events.create', Controller\CreateEventController::class)
        ->patch('/pickem-events/{id}', 'pickem.events.update', Controller\UpdateEventController::class)
        ->delete('/pickem-events/{id}', 'pickem.events.delete', Controller\DeleteEventController::class)
	    ->post('/pickem-events/{id}/result', 'pickem.events.result', Controller\EnterEventResultController::class)

        // Picks
        ->get('/pickem-picks', 'pickem.picks.index', Controller\ListPicksController::class)
        ->post('/pickem-picks', 'pickem.picks.create', Controller\CreatePickController::class)
        ->patch('/pickem-picks/{id}', 'pickem.picks.update', Controller\UpdatePickController::class)
        ->delete('/pickem-picks/{id}', 'pickem.picks.delete', Controller\DeletePickController::class)
        
        // Leaderboard
        ->get('/pickem-user-scores', 'pickem.leaderboard.index', Controller\ListLeaderboardController::class)

        // Public (Forum) data for filters
        ->get('/pickem-public-seasons', 'pickem.public.seasons.index', Controller\ListPublicSeasonsController::class)
        ->get('/pickem-public-teams', 'pickem.public.teams.index', Controller\ListPublicTeamsController::class)
        ->get('/pickem-public-weeks', 'pickem.public.weeks.index', Controller\ListPublicWeeksController::class)

        // Admin Tools
        ->post('/pickem/recalculate-all-scores', 'pickem.recalculate_scores', Controller\RecalculateAllScoresController::class),

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attributes(function ($serializer) { 
            $actor = $serializer->getActor();
            $settings = resolve(\Flarum\Settings\SettingsRepositoryInterface::class);
            
            return [
                'pickem.canManage' => $actor->can('pickem.manage'),
                'pickem.canView' => $actor->can('pickem.view'),
                'pickem.makePicks' => $actor->can('pickem.makePicks'),
                'pickem.reverseDisplay' => (bool) $settings->get('huseyinfiliz-pickem.reverse_display'),
            ];
        }),

    // Policies
    (new Extend\Policy())
        ->modelPolicy(Event::class, EventPolicy::class)
        ->modelPolicy(Pick::class, PickPolicy::class),

    // User Model Relationships
    (new Extend\Model(User::class))
        ->relationship('pickemPicks', function ($model) {
            return $model->hasMany(Pick::class, 'user_id');
        })
        ->relationship('pickemScores', function ($model) {
            return $model->hasMany(UserScore::class, 'user_id');
        }),

    // Event Listeners
    (new Extend\Event())
        ->listen(\Illuminate\Database\Events\Saved::class, Listener\UpdateUserScoresListener::class)
        ->listen(\Illuminate\Database\Events\Saved::class, Listener\SendResultNotificationsListener::class),

    // Notifications
    (new Extend\Notification())
        ->type(Notification\EventResultBlueprint::class, Serializer\EventSerializer::class, ['alert']),
];