<?php

namespace HuseyinFiliz\Awards\Models;

use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Award extends AbstractModel
{
    protected $table = 'awards';

    protected $dates = ['starts_at', 'ends_at', 'created_at', 'updated_at'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'year',
        'starts_at',
        'ends_at',
        'status',
        'show_live_votes',
        'image_url',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order');
    }

    public function getCategoryCountAttribute(): int
    {
        // Use pre-loaded count from withCount('categories') if available
        return $this->attributes['categories_count'] ?? $this->categories()->count();
    }

    public function getNomineeCountAttribute(): int
    {
        // If categories are already loaded, calculate from them to avoid N+1
        if ($this->relationLoaded('categories')) {
            return $this->categories->sum(fn($cat) => $cat->nominees_count ?? $cat->nominees()->count());
        }
        return Nominee::whereIn('category_id', $this->categories()->pluck('id'))->count();
    }

    public function getVoteCountAttribute(): int
    {
        // If categories are already loaded, calculate from them to avoid N+1
        if ($this->relationLoaded('categories')) {
            return $this->categories->sum('total_votes');
        }
        return Vote::whereIn('category_id', $this->categories()->pluck('id'))->count();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasEnded(): bool
    {
        return $this->status === 'ended';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isVotingOpen(): bool
    {
        $now = Carbon::now();
        return $this->isActive() &&
               $now->gte($this->starts_at) &&
               $now->lte($this->ends_at);
    }

    public function canShowVotes(): bool
    {
        return $this->isPublished() || ($this->isActive() && $this->show_live_votes);
    }

    public function hasStarted(): bool
    {
        return Carbon::now()->gte($this->starts_at);
    }

    /**
     * Get effective status based on current date.
     * If active but end date passed, treat as ended.
     */
    public function getEffectiveStatus(): string
    {
        // If manually set to ended or published, respect that
        if (in_array($this->status, ['ended', 'published'])) {
            return $this->status;
        }

        // If active but end date passed, treat as ended
        if ($this->status === 'active' && $this->ends_at && Carbon::now()->gt($this->ends_at)) {
            return 'ended';
        }

        return $this->status;
    }
}
