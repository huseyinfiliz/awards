<?php

namespace HuseyinFiliz\Awards\Search;

use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Nominee;
use Illuminate\Database\Eloquent\Builder;

class NomineeSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return Nominee::whereVisibleTo($actor)->select('award_nominees.*');
    }
}
