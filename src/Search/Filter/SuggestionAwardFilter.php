<?php

namespace HuseyinFiliz\Awards\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * Filters other_suggestions by award through the categories table.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class SuggestionAwardFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'award';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $id = $this->asInt($value);

        $state->getQuery()->whereIn('category_id', function ($query) use ($id, $negate) {
            $query->select('id')
                ->from('award_categories')
                ->where('award_id', $negate ? '!=' : '=', $id);
        });
    }
}
