<?php

return [
    'deduplication_ttl' => 86400, // 24 hours
    'default_channels' => ['mail', 'database'],
    'template_model' => \DynamicNotifier\Models\NotificationTemplate::class,
];
