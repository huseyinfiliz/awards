<?php

namespace HuseyinFiliz\Awards\Search;

use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Award;
use Illuminate\Database\Eloquent\Builder;

class AwardSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return Award::whereVisibleTo($actor)->select('awards.*');
    }
}
