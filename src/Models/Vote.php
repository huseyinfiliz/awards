<?php

namespace HuseyinFiliz\Awards\Models;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $nominee_id
 * @property int $category_id
 * @property string $created_at
 */
class Vote extends AbstractModel
{
    use ScopeVisibilityTrait;
    protected $table = 'award_votes';

    protected $fillable = [
        'nominee_id',
        'user_id',
        'category_id',
    ];

    public function nominee(): BelongsTo
    {
        return $this->belongsTo(Nominee::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
