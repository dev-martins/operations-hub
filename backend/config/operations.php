<?php

return [
    'attendance_integrations' => [
        'enabled' => env('ATTENDANCE_INTEGRATION_ENABLED', true),
        'connection' => env('ATTENDANCE_INTEGRATION_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'rabbitmq')),
        'queue' => env('ATTENDANCE_INTEGRATION_QUEUE', 'attendance-integrations'),
        'worker' => [
            'sleep' => (int) env('ATTENDANCE_INTEGRATION_WORKER_SLEEP', 1),
            'tries' => (int) env('ATTENDANCE_INTEGRATION_WORKER_TRIES', 3),
            'timeout' => (int) env('ATTENDANCE_INTEGRATION_WORKER_TIMEOUT', 30),
        ],
    ],
    'queue_overview_cache' => [
        'ttl' => env('QUEUE_OVERVIEW_CACHE_TTL', 120),
    ],
    'operational_attendances_cache' => [
        'ttl' => env('OPERATIONAL_ATTENDANCES_CACHE_TTL', 60),
    ],
];
