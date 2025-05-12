<?php

namespace DynamicNotifier\Listeners;

use DynamicNotifier\Events\GenericNotificationEvent;
use DynamicNotifier\Jobs\DispatchNotificationJob;
use DynamicNotifier\Jobs\DispatchBatchNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class DynamicNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(GenericNotificationEvent $event): void
    {
        $templateModel = Config::get('dynamic_notifier.template_model');
        $template = $templateModel::where('type', $event->type)->firstOrFail();

        $users = $event->users instanceof Collection
            ? $event->users
            : collect([$event->users]);

        if ($template->is_batchable) {
            DispatchBatchNotificationJob::dispatch($template, $users, $event->payload);
        } else {
            foreach ($users as $user) {
                DispatchNotificationJob::dispatch(
                    $user,
                    $template,
                    $event->payload
                );
            }
        }
    }
}
