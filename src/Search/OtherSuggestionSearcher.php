<?php

namespace HuseyinFiliz\Awards\Search;

use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use Illuminate\Database\Eloquent\Builder;

class OtherSuggestionSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return OtherSuggestion::whereVisibleTo($actor)->select('award_other_suggestions.*');
    }
}
