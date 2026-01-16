<?php

namespace HuseyinFiliz\Awards\Models;

use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nominee extends AbstractModel
{
    protected $table = 'award_nominees';

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'slug',
        'image_url',
        'metadata',
        'sort_order',
        'vote_adjustment',
    ];

    protected $casts = [
        'metadata' => 'array',
        'vote_adjustment' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * Get the real vote count from database (without adjustment)
     */
    public function getRealVoteCountAttribute(): int
    {
        return $this->votes()->count();
    }

    /**
     * Get the displayed vote count (real votes + adjustment)
     */
    public function getVoteCountAttribute(): int
    {
        return max(0, $this->real_vote_count + ($this->vote_adjustment ?? 0));
    }

    public function getVotePercentageAttribute(): float
    {
        $total = $this->category->total_votes;
        return $total > 0 ? round(($this->vote_count / $total) * 100, 1) : 0;
    }
}
