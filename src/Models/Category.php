<?php

namespace HuseyinFiliz\Awards\Models;

use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends AbstractModel
{
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
        return $this->hasMany(Nominee::class)->orderBy('sort_order');
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
        return $this->votes()->count();
    }
}
