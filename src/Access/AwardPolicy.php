<?php

namespace HuseyinFiliz\Awards\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Award;

class AwardPolicy extends AbstractPolicy
{
    public function createAward(User $actor): ?string
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }

        return null;
    }

    public function update(User $actor, Award $award): ?string
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }

        return null;
    }

    public function delete(User $actor, Award $award): ?string
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }

        return null;
    }

    public function view(User $actor, Award $award): ?string
    {
        if ($actor->hasPermission('awards.view')) {
            return $this->allow();
        }

        return null;
    }
}
