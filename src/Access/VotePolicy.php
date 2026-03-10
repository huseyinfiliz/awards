<?php

namespace HuseyinFiliz\Awards\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Vote;

class VotePolicy extends AbstractPolicy
{
    public function createVote(User $actor)
    {
        if ($actor->hasPermission('awards.vote')) {
            return $this->allow();
        }
    }

    public function delete(User $actor, Vote $vote)
    {
        if ($actor->id === $vote->user_id) {
            return $this->allow();
        }

        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }
    }
}
