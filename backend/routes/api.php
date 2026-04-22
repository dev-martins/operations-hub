<?php

use App\Http\Controllers\Api\V1\AttendanceAssignmentController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AttendanceEventController;
use App\Http\Controllers\Api\V1\AttendanceStatusController;
use App\Http\Controllers\Api\V1\AclController;
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
        Route::get('/auth/acl', [AclController::class, 'index'])->middleware('permission:acl.view');

        Route::get('/queues', [QueueController::class, 'index'])->middleware('permission:queues.view');
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view');
        Route::get('/attendances', [AttendanceController::class, 'index'])->middleware('permission:attendances.view');
        Route::post('/attendances', [AttendanceController::class, 'store'])->middleware('permission:attendances.create');
        Route::get('/attendances/{attendance}', [AttendanceController::class, 'show'])->middleware('permission:attendances.view');
        Route::patch('/attendances/{attendance}/status', [AttendanceStatusController::class, 'update'])->middleware('permission:attendances.update_status');
        Route::patch('/attendances/{attendance}/assignment', [AttendanceAssignmentController::class, 'update'])->middleware('permission:attendances.assign');
        Route::get('/attendances/{attendance}/events', [AttendanceEventController::class, 'index'])->middleware('permission:attendances.view');
    });
});
