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

        Attendance::query()->create([
            'tenant_id' => $user->tenant_id,
            'protocol' => 'AT-600002',
            'title' => 'Atendimento encerrado',
            'description' => 'Nao deve entrar na contagem operacional.',
            'type' => AttendanceType::REQUEST,
            'origin' => AttendanceOrigin::PORTAL,
            'priority' => AttendancePriority::LOW,
            'status' => AttendanceStatus::RESOLVED,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/queues');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'INT')
            ->assertJsonPath('data.0.waiting_count', 1);
    }

    public function test_admin_can_create_queue_for_the_authenticated_tenant(): void
    {
        $user = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'admin',
            ]),
        );

        $response = $this->postJson('/api/v1/queues', [
            'name' => 'Backoffice',
            'code' => 'back',
            'description' => 'Fila administrativa do tenant.',
            'active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Backoffice')
            ->assertJsonPath('data.code', 'BACK');

        $this->assertDatabaseHas('queues', [
            'tenant_id' => $user->tenant_id,
            'code' => 'BACK',
            'name' => 'Backoffice',
        ]);
    }

    public function test_supervisor_cannot_manage_queues_without_permission(): void
    {
        $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'supervisor',
            ]),
        );

        $response = $this->postJson('/api/v1/queues', [
            'name' => 'Backoffice',
            'code' => 'BACK',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('required_permission', 'queues.manage');
    }

    public function test_admin_can_update_queue_only_inside_the_same_tenant(): void
    {
        $admin = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'admin',
            ]),
        );

        $queue = OperationQueue::query()->create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Integrações',
            'code' => 'INT',
            'description' => 'Fila original.',
            'active' => true,
        ]);

        $otherQueue = OperationQueue::query()->create([
            'tenant_id' => $this->createTenant([
                'name' => 'Outro Tenant',
                'slug' => 'outro-tenant',
            ])->id,
            'name' => 'Externa',
            'code' => 'EXT',
            'description' => 'Fila externa.',
            'active' => true,
        ]);

        $this->patchJson("/api/v1/queues/{$queue->id}", [
            'name' => 'Integrações e parceiros',
            'active' => false,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Integrações e parceiros')
            ->assertJsonPath('data.active', false);

        $this->patchJson("/api/v1/queues/{$otherQueue->id}", [
            'name' => 'Nao deve atualizar',
        ])->assertNotFound();
    }
}
