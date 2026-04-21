<?php

namespace Tests\Feature;

use App\Enums\AttendanceOrigin;
use App\Enums\AttendancePriority;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\OperationQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_queues_with_waiting_counts(): void
    {
        $queue = OperationQueue::query()->create([
            'tenant_id' => 1,
            'name' => 'Integrações',
            'code' => 'INT',
            'active' => true,
        ]);

        Attendance::query()->create([
            'tenant_id' => 1,
            'protocol' => 'AT-600001',
            'title' => 'Teste de fila',
            'description' => 'Ocorrência para contagem.',
            'type' => AttendanceType::INTEGRATION,
            'origin' => AttendanceOrigin::API,
            'priority' => AttendancePriority::MEDIUM,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/queues');

        $response->assertOk()
            ->assertJsonPath('data.0.code', 'INT')
            ->assertJsonPath('data.0.waiting_count', 1);
    }
}
