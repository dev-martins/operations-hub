<?php

use App\Http\Controllers\Api\V1\AttendanceAssignmentController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AttendanceEventController;
use App\Http\Controllers\Api\V1\AttendanceStatusController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\QueueController;
use App\Http\Controllers\Api\V1\UserController;
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

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/queues', [QueueController::class, 'index']);
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/attendances', [AttendanceController::class, 'index']);
        Route::post('/attendances', [AttendanceController::class, 'store']);
        Route::get('/attendances/{attendance}', [AttendanceController::class, 'show']);
        Route::patch('/attendances/{attendance}/status', [AttendanceStatusController::class, 'update']);
        Route::patch('/attendances/{attendance}/assignment', [AttendanceAssignmentController::class, 'update']);
        Route::get('/attendances/{attendance}/events', [AttendanceEventController::class, 'index']);
    });
});
