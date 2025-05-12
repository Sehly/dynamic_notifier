<?php

namespace DynamicNotifier\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use DynamicNotifier\Events\GenericNotificationEvent;

class NotifierEngine
{
    public function send(string $type, $users, array $payload = [])
    {
        $users = $users instanceof Collection ? $users : collect([$users]);

        Event::dispatch(new GenericNotificationEvent(
            type: $type,
            users: $users,
            payload: $payload
        ));
    }
}
