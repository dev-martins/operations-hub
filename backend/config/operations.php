<?php

return [
    'attendance_integrations' => [
        'enabled' => env('ATTENDANCE_INTEGRATION_ENABLED', true),
        'connection' => env('ATTENDANCE_INTEGRATION_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'rabbitmq')),
        'queue' => env('ATTENDANCE_INTEGRATION_QUEUE', 'attendance-integrations'),
    ],
    'queue_overview_cache' => [
        'ttl' => env('QUEUE_OVERVIEW_CACHE_TTL', 120),
    ],
    'operational_attendances_cache' => [
        'ttl' => env('OPERATIONAL_ATTENDANCES_CACHE_TTL', 60),
    ],
];
