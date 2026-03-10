<?php

namespace HuseyinFiliz\Awards\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * Filters by category_id. Used on NomineeSearcher and OtherSuggestionSearcher.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class CategoryFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'category';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $id = $this->asInt($value);

        $state->getQuery()->where('category_id', $negate ? '!=' : '=', $id);
    }
}
