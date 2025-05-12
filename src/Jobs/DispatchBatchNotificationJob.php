<?php

namespace DynamicNotifier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use DynamicNotifier\Models\NotificationTemplate;
use DynamicNotifier\Models\BatchNotification;
use DynamicNotifier\Models\NotificationBatchLog;
use DynamicNotifier\Services\NotificationTemplateParser;
use DynamicNotifier\Notifications\DynamicNotification;

class DispatchBatchNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected NotificationTemplate $template;
    protected Collection $users;
    protected array $payload;
    protected ?array $preFetchedTemplates;

    public function __construct(NotificationTemplate $template, Collection $users, array $payload, array $preFetchedTemplates = null)
    {
        $this->template = $template;
        $this->users = $users;
        $this->payload = $payload;
        $this->preFetchedTemplates = $preFetchedTemplates;
    }

    public function handle(NotificationTemplateParser $parser): void
    {
        $batch = DB::transaction(fn () => BatchNotification::create([
            'template_id' => $this->template->id,
            'parsed_body' => $this->template->body,
            'success_count' => 0,
            'failure_count' => 0,
        ]));

        foreach ($this->users as $user) {
            $cacheKey = "notif_sent:{$this->template->type}:{$user->id}";

            if (!Cache::add($cacheKey, true, now()->addSeconds(config('dynamic_notifier.deduplication_ttl', 86400)))) {
                Log::info("⏭️ Skipped duplicate notification to user #{$user->id}");
                continue;
            }

            try {
                $templateToUse = $this->preFetchedTemplates['mail'][0] ?? $this->template;

                $parsed = $parser->parse([
                    'user' => $user,
                    'payload' => $this->payload,
                ], $templateToUse->body, $templateToUse->header);

                $user->notify(new DynamicNotification(
                    $templateToUse->channel,
                    $parsed['body'],
                    $parsed['header']
                ));

                $batch->increment('success_count');
                Log::info("📤 Sent batch notification to user #{$user->id}");
            } catch (\Throwable $e) {
                Log::error("❌ Failed batch send to user #{$user->id}: " . $e->getMessage());

                $batch->increment('failure_count');
                NotificationBatchLog::create([
                    'batch_id' => $batch->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'status' => 'failed',
                ]);
            }
        }
    }
}
