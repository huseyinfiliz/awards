<?php

namespace HuseyinFiliz\Awards\Models;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string|null $slug
 * @property string|null $description
 * @property string|null $image_url
 * @property array|null $metadata
 * @property int $sort_order
 * @property int $vote_count
 * @property int $real_vote_count
 * @property int|null $vote_adjustment
 * @property float $vote_percentage
 * @property int $total_votes
 */
class Nominee extends AbstractModel
{
    use ScopeVisibilityTrait;
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

    /**
     * Hide sensitive fields from array/JSON serialization
     * These are only exposed through the serializer with proper permission checks
     */
    protected $hidden = [
        'vote_adjustment',
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
