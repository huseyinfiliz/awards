<?php

namespace HuseyinFiliz\Awards\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Category;

class CategoryPolicy extends AbstractPolicy
{
    public function createCategory(User $actor)
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }
    }

    public function update(User $actor, Category $category)
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }
    }

    public function delete(User $actor, Category $category)
    {
        if ($actor->hasPermission('awards.manage')) {
            return $this->allow();
        }
    }
}
