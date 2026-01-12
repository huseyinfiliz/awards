<?php

namespace HuseyinFiliz\Awards\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Award;

class AwardPolicy extends AbstractPolicy
{
    public function can(User $actor, string $ability)
    {
        if ($actor->hasPermission('awards.manage')) {
            return true;
        }
    }

    public function view(User $actor, Award $award)
    {
        return $actor->hasPermission('awards.view');
    }
}
