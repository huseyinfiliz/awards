<?php

namespace HuseyinFiliz\Awards\Search;

use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Vote;
use Illuminate\Database\Eloquent\Builder;

class VoteSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return Vote::whereVisibleTo($actor)->select('award_votes.*');
    }
}
