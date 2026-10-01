<?php

namespace HuseyinFiliz\Awards\Models;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property int         $award_id
 * @property string      $name
 * @property string      $slug
 * @property string|null $description
 * @property int         $sort_order
 * @property bool        $allow_other
 * @property int         $total_votes
 * @property int         $nominee_count
 * @property-read Award|null $award
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Nominee> $nominees
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Vote> $votes
 * @property-read int|null $nominees_count
 * @property-read int|null $votes_count
 */
class Category extends AbstractModel
{
    use ScopeVisibilityTrait;
    protected $table = 'award_categories';

    protected $fillable = [
        'award_id',
        'name',
        'slug',
        'description',
        'sort_order',
        'allow_other',
    ];

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function nominees(): HasMany
    {
        return $this->hasMany(Nominee::class)->orderBy('sort_order')->withCount('votes');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function otherSuggestions(): HasMany
    {
        return $this->hasMany(OtherSuggestion::class);
    }

    public function pendingSuggestions(): HasMany
    {
        return $this->hasMany(OtherSuggestion::class)->where('status', 'pending');
    }

    public function getTotalVotesAttribute(): int
    {
        if (isset($this->attributes['votes_count'])) {
            return (int) $this->attributes['votes_count'];
        }
        if ($this->relationLoaded('votes')) {
            return $this->votes->count();
        }

        return $this->votes()->count();
    }

    public function getNomineeCountAttribute(): int
    {
        if (isset($this->attributes['nominees_count'])) {
            return (int) $this->attributes['nominees_count'];
        }
        if ($this->relationLoaded('nominees')) {
            return $this->nominees->count();
        }

        return $this->nominees()->count();
    }
}
