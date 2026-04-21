<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/status', function (): JsonResponse {
        return response()->json([
            'name' => config('app.name'),
            'status' => 'ok',
            'ambiente' => app()->environment(),
            'objetivo' => 'API da central de atendimento operacional com foco em filas, SLA e integrações corporativas.',
            'stacks' => [
                'laravel',
                'vue',
                'mysql',
                'redis',
                'rabbitmq',
                'docker',
            ],
        ]);
    });
});
