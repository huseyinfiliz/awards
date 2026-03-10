<?php

namespace HuseyinFiliz\Awards\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * Filters by award_id. Used on CategorySearcher and OtherSuggestionSearcher.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class AwardFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'award';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $id = $this->asInt($value);

        $state->getQuery()->where('award_id', $negate ? '!=' : '=', $id);
    }
}
