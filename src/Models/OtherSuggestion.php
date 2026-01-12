<?php

namespace HuseyinFiliz\Awards\Models;

use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtherSuggestion extends AbstractModel
{
    protected $table = 'award_other_suggestions';

    protected $fillable = [
        'category_id',
        'user_id',
        'name',
        'status',
        'merged_to_nominee_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mergedToNominee(): BelongsTo
    {
        return $this->belongsTo(Nominee::class, 'merged_to_nominee_id');
    }
}
