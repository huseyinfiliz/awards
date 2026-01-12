<?php

namespace HuseyinFiliz\Pickem\Access;

use Flarum\User\User;
use Flarum\User\Access\AbstractPolicy; 
use HuseyinFiliz\Pickem\Event;

class EventPolicy extends AbstractPolicy
{
    /**
     * @param User $actor
     * @param string $ability
     * @return bool|void
     */
    public function can(User $actor, string $ability)
    {
        // 'pickem.view' iznine sahip olanlar
        // 'view' (görüntüleme) yetkisine sahiptir.
        if ($actor->can('pickem.view') && $ability === 'view') {
            return true;
        }
    }

    /**
     * This will be called by the Serializer for each event.
     * * @param User $actor
     * @param Event $event
     * @return bool
     */
    public function view(User $actor, Event $event)
    {
        return $actor->can('pickem.view');
    }
}