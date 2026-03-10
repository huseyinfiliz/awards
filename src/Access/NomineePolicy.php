<?php

namespace HuseyinFiliz\Awards\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Nominee;

class NomineePolicy extends AbstractPolicy
{
    public function createNominee(User $actor)
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }
    }

    public function update(User $actor, Nominee $nominee)
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }
    }

    public function delete(User $actor, Nominee $nominee)
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }
    }
}
