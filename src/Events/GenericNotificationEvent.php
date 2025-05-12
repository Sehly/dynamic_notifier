<?php

namespace DynamicNotifier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class GenericNotificationEvent
{
    use Dispatchable, SerializesModels;

    public string $type;
    public Collection $users;
    public array $payload;

    public function __construct(string $type, Collection $users, array $payload = [])
    {
        $this->type = $type;
        $this->users = $users;
        $this->payload = $payload;
    }
}
