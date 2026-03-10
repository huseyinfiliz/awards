<?php

namespace HuseyinFiliz\Awards\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\OtherSuggestion;

class OtherSuggestionPolicy extends AbstractPolicy
{
    public function createOtherSuggestion(User $actor)
    {
        if ($actor->hasPermission('awards.vote')) {
            return $this->allow();
        }
    }

    public function update(User $actor, OtherSuggestion $suggestion)
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }
    }

    public function delete(User $actor, OtherSuggestion $suggestion)
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }
    }
}
