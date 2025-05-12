<?php

namespace DynamicNotifier\Services;

use Illuminate\Support\Facades\Blade;

class NotificationTemplateParser
{
    public function parse(array $data, string $body, ?string $header = null): array
    {
        $context = array_merge($data, [
            'user' => $data['user'],
            'payload' => $data['payload'],
        ]);

        $parsedBody = Blade::render($body, $context);
        $parsedHeader = $header ? Blade::render($header, $context) : null;

        return [
            'body' => $parsedBody,
            'header' => $parsedHeader,
        ];
    }
}
