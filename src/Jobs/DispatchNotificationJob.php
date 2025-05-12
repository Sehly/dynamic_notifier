<?php

namespace DynamicNotifier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use DynamicNotifier\Models\NotificationTemplate;
use DynamicNotifier\Services\NotificationTemplateParser;
use DynamicNotifier\Notifications\DynamicNotification;

class DispatchNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notifiable;
    protected NotificationTemplate $template;
    protected array $payload;
    protected ?array $preFetchedTemplates;

    public function __construct($notifiable, NotificationTemplate $template, array $payload, array $preFetchedTemplates = null)
    {
        $this->notifiable = $notifiable;
        $this->template = $template;
        $this->payload = $payload;
        $this->preFetchedTemplates = $preFetchedTemplates;
    }

    public function handle(NotificationTemplateParser $parser): void
    {
        $cacheKey = "notif_sent:{$this->template->type}:{$this->notifiable->id}";

        if (!Cache::add($cacheKey, true, now()->addSeconds(config('dynamic_notifier.deduplication_ttl', 86400)))) {
            Log::info("⏭️ Skipped duplicate notification to user #{$this->notifiable->id}");
            return;
        }

        try {
            $templateToUse = $this->preFetchedTemplates['mail'][0] ?? $this->template;

            $parsed = $parser->parse([
                'user' => $this->notifiable,
                'payload' => $this->payload,
            ], $templateToUse->body, $templateToUse->header);

            $this->notifiable->notify(new DynamicNotification(
                $templateToUse->channel,
                $parsed['body'],
                $parsed['header']
            ));

            Log::info("📤 Sent notification to user #{$this->notifiable->id} ({$this->notifiable->email})");
        } catch (\Throwable $e) {
            Log::error("❌ Failed to send notification to user #{$this->notifiable->id}: " . $e->getMessage());
        }
    }
}
