<?php

return [
    'attendance_integrations' => [
        'enabled' => env('ATTENDANCE_INTEGRATION_ENABLED', true),
        'connection' => env('ATTENDANCE_INTEGRATION_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'rabbitmq')),
        'queue' => env('ATTENDANCE_INTEGRATION_QUEUE', 'attendance-integrations'),
    ],
];
