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

    public function test_it_lists_queues_with_waiting_counts_only_for_the_authenticated_tenant(): void
    {
        $user = $this->actingAsTenantUser();
        $otherTenant = $this->createTenant([
            'name' => 'Outro Tenant',
            'slug' => 'outro-tenant',
        ]);

        $queue = OperationQueue::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Integrações',
            'code' => 'INT',
            'active' => true,
        ]);

        OperationQueue::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Fila Externa',
            'code' => 'EXT',
            'active' => true,
        ]);

        Attendance::query()->create([
            'tenant_id' => $user->tenant_id,
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
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'INT')
            ->assertJsonPath('data.0.waiting_count', 1);
    }
}
