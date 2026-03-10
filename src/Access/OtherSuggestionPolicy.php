<?php

namespace HuseyinFiliz\Awards\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\OtherSuggestion;

class OtherSuggestionPolicy extends AbstractPolicy
{
    public function createOtherSuggestion(User $actor): ?string
    {
        if ($actor->hasPermission('awards.vote')) {
            return $this->allow();
        }

        return null;
    }

    public function update(User $actor, OtherSuggestion $suggestion): ?string
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }

        return null;
    }

    public function delete(User $actor, OtherSuggestion $suggestion): ?string
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }

        return null;
    }
}
