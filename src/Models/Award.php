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
}
