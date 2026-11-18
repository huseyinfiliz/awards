<?php

namespace HuseyinFiliz\Awards\Integration;

use Flarum\Audit\AuditLogger;
use HuseyinFiliz\Awards\Models\Award;
use HuseyinFiliz\Awards\Models\Category;
use HuseyinFiliz\Awards\Models\Nominee;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;

class AuditIntegration
{
    /** @var string[] */
    public static array $actions = [
        'awards.award.created', 'awards.award.updated', 'awards.award.deleted',
        'awards.category.created', 'awards.category.updated', 'awards.category.deleted',
        'awards.nominee.created', 'awards.nominee.updated', 'awards.nominee.deleted',
        'awards.results.published', 'awards.nominee.votes_adjusted',
        'awards.suggestion.moderated',
    ];

    public function __invoke(Container $container): void
    {
        foreach ([Award::class => 'award', Category::class => 'category', Nominee::class => 'nominee'] as $class => $type) {
            $class::created(fn (Model $model) => AuditLogger::log('awards.'.$type.'.created', [$type.'_id' => $model->getKey()]));
            $class::deleted(fn (Model $model) => AuditLogger::log('awards.'.$type.'.deleted', [$type.'_id' => $model->getKey()]));
            $class::updated(function (Model $model) use ($type) {
                $fields = array_values(array_diff(array_keys($model->getChanges()), ['updated_at']));
                if ($fields === []) {
                    return;
                }
                AuditLogger::log('awards.'.$type.'.updated', [$type.'_id' => $model->getKey(), 'fields' => $fields]);

                if ($model instanceof Award && $model->wasChanged('status') && $model->status === 'published') {
                    AuditLogger::log('awards.results.published', ['award_id' => $model->id]);
                }
                if ($model instanceof Nominee && $model->wasChanged('vote_adjustment')) {
                    AuditLogger::log('awards.nominee.votes_adjusted', [
                        'nominee_id'     => $model->id,
                        'old_adjustment' => (int) $model->getRawOriginal('vote_adjustment'),
                        'new_adjustment' => (int) $model->vote_adjustment,
                    ]);
                }
            });
        }

        // Record moderation decisions, never individual ballots or suggestion text.
        OtherSuggestion::updated(function (OtherSuggestion $suggestion) {
            if ($suggestion->wasChanged('status')) {
                AuditLogger::log('awards.suggestion.moderated', [
                    'suggestion_id' => $suggestion->id,
                    'category_id'   => $suggestion->category_id,
                    'old_status'    => $suggestion->getRawOriginal('status'),
                    'new_status'    => $suggestion->status,
                ]);
            }
        });
    }
}
