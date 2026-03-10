<?php

namespace HuseyinFiliz\Awards\Search;

use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Category;
use Illuminate\Database\Eloquent\Builder;

class CategorySearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return Category::whereVisibleTo($actor)->select('award_categories.*');
    }
}
