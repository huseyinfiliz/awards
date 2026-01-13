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
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function getVoteCountAttribute(): int
    {
        return $this->votes()->count();
    }

    public function getVotePercentageAttribute(): float
    {
        $total = $this->category->total_votes;
        return $total > 0 ? round(($this->vote_count / $total) * 100, 1) : 0;
    }
}
