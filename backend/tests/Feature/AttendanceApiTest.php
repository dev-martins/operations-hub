<?php

namespace Tests\Feature;

use App\Enums\AttendanceOrigin;
use App\Enums\AttendancePriority;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Jobs\PropagateAttendanceToLegacy;
use App\Models\Attendance;
use App\Models\OperationQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_creates_an_attendance_and_registers_the_first_event(): void
    {
        config()->set('operations.attendance_integrations.enabled', false);

        $user = $this->actingAsTenantUser();

        $queue = OperationQueue::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Integrações',
            'code' => 'INT',
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/attendances', [
            'title' => 'Falha de sincronização com ERP',
            'description' => 'O payload de faturamento não retornou confirmação.',
            'type' => AttendanceType::INTEGRATION->value,
            'origin' => AttendanceOrigin::ERP->value,
            'priority' => AttendancePriority::HIGH->value,
            'queue_id' => $queue->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Falha de sincronização com ERP')
            ->assertJsonPath('data.tenant_id', $user->tenant_id)
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonPath('data.creator.id', $user->id)
            ->assertJsonPath('data.creator.name', $user->name)
            ->assertJsonPath('data.status', AttendanceStatus::OPEN->value)
            ->assertJsonPath('data.queue.code', 'INT');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('attendance_events', 1);
        $this->assertDatabaseHas('attendance_events', [
            'type' => 'created',
            'created_by' => $user->id,
        ]);
    }

    public function test_it_dispatches_legacy_synchronization_when_attendance_is_created(): void
    {
        config()->set('operations.attendance_integrations.enabled', true);
        config()->set('operations.attendance_integrations.connection', 'rabbitmq');
        config()->set('operations.attendance_integrations.queue', 'attendance-integrations');

        Queue::fake();

        $user = $this->actingAsTenantUser();

        $queue = OperationQueue::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Integrações',
            'code' => 'INT',
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/attendances', [
            'title' => 'Payload pendente no legado',
            'description' => 'A integração secundária deve seguir de forma assíncrona.',
            'type' => AttendanceType::INTEGRATION->value,
            'origin' => AttendanceOrigin::ERP->value,
            'priority' => AttendancePriority::HIGH->value,
            'queue_id' => $queue->id,
        ]);

        $response->assertCreated();

        Queue::assertPushed(PropagateAttendanceToLegacy::class, function (PropagateAttendanceToLegacy $job) use ($user): bool {
            return $job->tenantId === $user->tenant_id
                && $job->trigger === 'created'
                && $job->connection === 'rabbitmq'
                && $job->queue === 'attendance-integrations';
        });

        $this->assertDatabaseHas('attendance_events', [
            'type' => 'legacy_sync_requested',
            'created_by' => $user->id,
        ]);
    }

    public function test_it_validates_required_fields_when_creating_an_attendance(): void
    {
        $this->actingAsTenantUser();

        $response = $this->postJson('/api/v1/attendances', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'description',
                'type',
                'origin',
                'priority',
                'queue_id',
            ]);
    }

    public function test_it_lists_attendances_with_queue_information_for_the_authenticated_tenant(): void
    {
        $user = $this->actingAsTenantUser();
        $otherTenant = $this->createTenant([
            'name' => 'Tenant Externo',
            'slug' => 'tenant-externo-atend',
        ]);

        $queue = OperationQueue::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Suporte N1',
            'code' => 'SUP-N1',
            'active' => true,
        ]);

        $otherQueue = OperationQueue::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Outra fila',
            'code' => 'OUT',
            'active' => true,
        ]);

        Attendance::query()->create([
            'tenant_id' => $user->tenant_id,
            'protocol' => 'AT-500001',
            'title' => 'Atendimento em aberto',
            'description' => 'Falha operacional em análise.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::MANUAL,
            'priority' => AttendancePriority::MEDIUM,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        Attendance::query()->create([
            'tenant_id' => $otherTenant->id,
            'protocol' => 'AT-500099',
            'title' => 'Atendimento externo',
            'description' => 'Nao deve aparecer.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::MANUAL,
            'priority' => AttendancePriority::LOW,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $otherQueue->id,
            'opened_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/attendances');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.protocol', 'AT-500001')
            ->assertJsonPath('data.0.queue.code', 'SUP-N1');
    }

    public function test_it_shows_a_single_attendance_with_events(): void
    {
        $user = $this->actingAsTenantUser();

        $queue = OperationQueue::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Financeiro',
            'code' => 'FIN',
            'active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $user->tenant_id,
            'protocol' => 'AT-500002',
            'title' => 'Divergência de pagamento',
            'description' => 'Pagamento aprovado sem baixa no ERP.',
            'type' => AttendanceType::FINANCIAL,
            'origin' => AttendanceOrigin::PDV,
            'priority' => AttendancePriority::CRITICAL,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'created_by' => $user->id,
            'opened_at' => now(),
        ]);

        $attendance->events()->create([
            'tenant_id' => $user->tenant_id,
            'type' => 'created',
            'description' => 'Atendimento criado.',
            'created_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/attendances/{$attendance->id}");

        $response->assertOk()
            ->assertJsonPath('data.protocol', 'AT-500002')
            ->assertJsonPath('data.creator.id', $user->id)
            ->assertJsonPath('data.events.0.type', 'created');
    }

    public function test_it_changes_status_and_requires_resolution_notes_when_resolving(): void
    {
        $user = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'supervisor',
            ]),
        );

        $queue = OperationQueue::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Críticos',
            'code' => 'CRT',
            'active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $user->tenant_id,
            'protocol' => 'AT-500003',
            'title' => 'Fila travada',
            'description' => 'Fila de críticos com atraso.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::API,
            'priority' => AttendancePriority::CRITICAL,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $invalid = $this->patchJson("/api/v1/attendances/{$attendance->id}/status", [
            'status' => AttendanceStatus::RESOLVED->value,
        ]);

        $invalid->assertUnprocessable()
            ->assertJsonValidationErrors(['resolution_notes']);

        $valid = $this->patchJson("/api/v1/attendances/{$attendance->id}/status", [
            'status' => AttendanceStatus::RESOLVED->value,
            'resolution_notes' => 'Fila normalizada após reprocessamento.',
        ]);

        $valid->assertOk()
            ->assertJsonPath('data.status', AttendanceStatus::RESOLVED->value)
            ->assertJsonPath('data.resolution_notes', 'Fila normalizada após reprocessamento.');

        $this->assertDatabaseHas('attendance_events', [
            'attendance_id' => $attendance->id,
            'type' => 'status_changed',
            'created_by' => $user->id,
        ]);
    }

    public function test_it_rejects_status_transition_when_new_status_matches_the_current_one(): void
    {
        $actor = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'supervisor',
            ]),
        );

        $queue = OperationQueue::query()->create([
            'tenant_id' => $actor->tenant_id,
            'name' => 'Suporte N2',
            'code' => 'SUP-N2',
            'active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $actor->tenant_id,
            'protocol' => 'AT-500003Z',
            'title' => 'Sem alteração efetiva de status',
            'description' => 'Não deve registrar evento redundante.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::API,
            'priority' => AttendancePriority::MEDIUM,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/attendances/{$attendance->id}/status", [
            'status' => AttendanceStatus::OPEN->value,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseMissing('attendance_events', [
            'attendance_id' => $attendance->id,
            'type' => 'status_changed',
        ]);
    }

    public function test_operator_can_update_status_when_attendance_is_unassigned(): void
    {
        $actor = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'operator',
            ]),
        );

        $queue = OperationQueue::query()->create([
            'tenant_id' => $actor->tenant_id,
            'name' => 'Críticos',
            'code' => 'CRT',
            'active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $actor->tenant_id,
            'protocol' => 'AT-500003A',
            'title' => 'Fila aguardando resposta',
            'description' => 'Necessita avanço operacional.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::API,
            'priority' => AttendancePriority::HIGH,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/attendances/{$attendance->id}/status", [
            'status' => AttendanceStatus::IN_PROGRESS->value,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', AttendanceStatus::IN_PROGRESS->value);
    }

    public function test_operator_cannot_update_status_for_attendance_assigned_to_another_user(): void
    {
        $actor = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'operator',
            ]),
        );

        $queue = OperationQueue::query()->create([
            'tenant_id' => $actor->tenant_id,
            'name' => 'Críticos',
            'code' => 'CRT',
            'active' => true,
        ]);

        $otherOperator = User::factory()->create([
            'tenant_id' => $actor->tenant_id,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $actor->tenant_id,
            'protocol' => 'AT-500003B',
            'title' => 'Atendimento de outro operador',
            'description' => 'Já está sob responsabilidade de outro analista.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::API,
            'priority' => AttendancePriority::MEDIUM,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'assigned_to' => $otherOperator->id,
            'opened_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/attendances/{$attendance->id}/status", [
            'status' => AttendanceStatus::IN_PROGRESS->value,
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Este atendimento só pode ser atualizado pelo operador responsável ou pela supervisão.');
    }

    public function test_operator_cannot_resolve_attendance(): void
    {
        $actor = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'operator',
            ]),
        );

        $queue = OperationQueue::query()->create([
            'tenant_id' => $actor->tenant_id,
            'name' => 'Críticos',
            'code' => 'CRT',
            'active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $actor->tenant_id,
            'protocol' => 'AT-500003C',
            'title' => 'Tentativa de resolução indevida',
            'description' => 'Operador não deveria encerrar formalmente.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::API,
            'priority' => AttendancePriority::HIGH,
            'status' => AttendanceStatus::IN_PROGRESS,
            'queue_id' => $queue->id,
            'assigned_to' => $actor->id,
            'opened_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/attendances/{$attendance->id}/status", [
            'status' => AttendanceStatus::RESOLVED->value,
            'resolution_notes' => 'Tentando concluir diretamente.',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Usuário sem permissão para resolver atendimentos.');
    }

    public function test_it_assigns_an_attendance_to_a_user(): void
    {
        $actor = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'supervisor',
            ]),
        );

        $queue = OperationQueue::query()->create([
            'tenant_id' => $actor->tenant_id,
            'name' => 'Suporte N1',
            'code' => 'SUP-N1',
            'active' => true,
        ]);

        $assignee = User::factory()->create([
            'tenant_id' => $actor->tenant_id,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $actor->tenant_id,
            'protocol' => 'AT-500004',
            'title' => 'Falha de atualização cadastral',
            'description' => 'Cadastro travado na sincronização.',
            'type' => AttendanceType::REQUEST,
            'origin' => AttendanceOrigin::PORTAL,
            'priority' => AttendancePriority::LOW,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/attendances/{$attendance->id}/assignment", [
            'assigned_to' => $assignee->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.assigned_to', $assignee->id)
            ->assertJsonPath('data.assignee.id', $assignee->id);

        $this->assertDatabaseHas('attendance_events', [
            'attendance_id' => $attendance->id,
            'type' => 'assigned',
            'created_by' => $actor->id,
        ]);
    }

    public function test_it_blocks_assignment_for_roles_without_permission(): void
    {
        $actor = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'operator',
            ]),
        );

        $queue = OperationQueue::query()->create([
            'tenant_id' => $actor->tenant_id,
            'name' => 'Suporte N1',
            'code' => 'SUP-N1',
            'active' => true,
        ]);

        $assignee = User::factory()->create([
            'tenant_id' => $actor->tenant_id,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $actor->tenant_id,
            'protocol' => 'AT-500005',
            'title' => 'Reatribuição necessária',
            'description' => 'Carga operacional precisa ser redistribuída.',
            'type' => AttendanceType::REQUEST,
            'origin' => AttendanceOrigin::PORTAL,
            'priority' => AttendancePriority::MEDIUM,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/attendances/{$attendance->id}/assignment", [
            'assigned_to' => $assignee->id,
        ]);

        $response->assertForbidden()
            ->assertJsonPath('required_permission', 'attendances.assign');
    }

    public function test_it_blocks_assignment_for_terminal_attendances(): void
    {
        $actor = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'supervisor',
            ]),
        );

        $queue = OperationQueue::query()->create([
            'tenant_id' => $actor->tenant_id,
            'name' => 'Suporte N1',
            'code' => 'SUP-N1',
            'active' => true,
        ]);

        $assignee = User::factory()->create([
            'tenant_id' => $actor->tenant_id,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $actor->tenant_id,
            'protocol' => 'AT-500006',
            'title' => 'Atendimento encerrado',
            'description' => 'Não deve ser redistribuído.',
            'type' => AttendanceType::REQUEST,
            'origin' => AttendanceOrigin::PORTAL,
            'priority' => AttendancePriority::LOW,
            'status' => AttendanceStatus::RESOLVED,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/attendances/{$attendance->id}/assignment", [
            'assigned_to' => $assignee->id,
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Atendimentos encerrados não podem ser reatribuídos.');
    }

    public function test_it_returns_not_found_when_trying_to_show_an_attendance_from_another_tenant(): void
    {
        $actor = $this->actingAsTenantUser();
        $otherTenant = $this->createTenant([
            'name' => 'Tenant Blindado',
            'slug' => 'tenant-blindado',
        ]);

        $queue = OperationQueue::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Fila externa',
            'code' => 'EXT',
            'active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $otherTenant->id,
            'protocol' => 'AT-500007',
            'title' => 'Atendimento de outro tenant',
            'description' => 'Não deve ser visível.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::MANUAL,
            'priority' => AttendancePriority::LOW,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/attendances/{$attendance->id}");

        $response->assertNotFound();
    }

    public function test_it_returns_not_found_when_trying_to_update_status_from_another_tenant(): void
    {
        $actor = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'supervisor',
            ]),
        );
        $otherTenant = $this->createTenant([
            'name' => 'Tenant Blindado Status',
            'slug' => 'tenant-blindado-status',
        ]);

        $queue = OperationQueue::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Fila externa',
            'code' => 'EXT-ST',
            'active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $otherTenant->id,
            'protocol' => 'AT-500008',
            'title' => 'Status fora do tenant',
            'description' => 'Não deve ser localizado para alteração.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::API,
            'priority' => AttendancePriority::HIGH,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/attendances/{$attendance->id}/status", [
            'status' => AttendanceStatus::IN_PROGRESS->value,
        ]);

        $response->assertNotFound();
    }

    public function test_it_returns_not_found_when_trying_to_assign_attendance_from_another_tenant(): void
    {
        $actor = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'supervisor',
            ]),
        );
        $otherTenant = $this->createTenant([
            'name' => 'Tenant Blindado Assignment',
            'slug' => 'tenant-blindado-assignment',
        ]);

        $queue = OperationQueue::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Fila externa',
            'code' => 'EXT-AS',
            'active' => true,
        ]);

        $assignee = User::factory()->create([
            'tenant_id' => $actor->tenant_id,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $otherTenant->id,
            'protocol' => 'AT-500009',
            'title' => 'Atribuição fora do tenant',
            'description' => 'Não deve ser localizada para redistribuição.',
            'type' => AttendanceType::REQUEST,
            'origin' => AttendanceOrigin::PORTAL,
            'priority' => AttendancePriority::MEDIUM,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/attendances/{$attendance->id}/assignment", [
            'assigned_to' => $assignee->id,
        ]);

        $response->assertNotFound();
    }

    public function test_it_can_list_only_operational_attendances_when_requested(): void
    {
        $user = $this->actingAsTenantUser();

        $queue = OperationQueue::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Operacao',
            'code' => 'OPE',
            'active' => true,
        ]);

        Attendance::query()->create([
            'tenant_id' => $user->tenant_id,
            'protocol' => 'AT-500008',
            'title' => 'Ainda em fluxo',
            'description' => 'Deve aparecer na fila operacional.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::MANUAL,
            'priority' => AttendancePriority::MEDIUM,
            'status' => AttendanceStatus::IN_PROGRESS,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        Attendance::query()->create([
            'tenant_id' => $user->tenant_id,
            'protocol' => 'AT-500009',
            'title' => 'Ja encerrado',
            'description' => 'Nao deve aparecer na fila operacional.',
            'type' => AttendanceType::REQUEST,
            'origin' => AttendanceOrigin::PORTAL,
            'priority' => AttendancePriority::LOW,
            'status' => AttendanceStatus::RESOLVED,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/attendances?operational_only=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.protocol', 'AT-500008');
    }

    public function test_it_reuses_cached_operational_attendance_list_until_the_cache_is_invalidated(): void
    {
        $user = $this->actingAsTenantUser();

        $queue = OperationQueue::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Operacao',
            'code' => 'OPE',
            'active' => true,
        ]);

        Attendance::query()->create([
            'tenant_id' => $user->tenant_id,
            'protocol' => 'AT-500010',
            'title' => 'Primeiro atendimento operacional',
            'description' => 'Deve compor a leitura inicial cacheada.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::MANUAL,
            'priority' => AttendancePriority::MEDIUM,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $this->getJson('/api/v1/attendances?operational_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        Attendance::query()->create([
            'tenant_id' => $user->tenant_id,
            'protocol' => 'AT-500011',
            'title' => 'Segundo atendimento operacional',
            'description' => 'Nao deve aparecer antes da invalidação.',
            'type' => AttendanceType::REQUEST,
            'origin' => AttendanceOrigin::PORTAL,
            'priority' => AttendancePriority::LOW,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $this->getJson('/api/v1/attendances?operational_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_invalidates_cached_operational_attendance_list_when_creating_an_attendance(): void
    {
        config()->set('operations.attendance_integrations.enabled', false);

        $user = $this->actingAsTenantUser();

        $queue = OperationQueue::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Operacao',
            'code' => 'OPE',
            'active' => true,
        ]);

        $this->getJson('/api/v1/attendances?operational_only=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->postJson('/api/v1/attendances', [
            'title' => 'Carga recém-chegada',
            'description' => 'Deve aparecer após invalidar o cache da fila operacional.',
            'type' => AttendanceType::INCIDENT->value,
            'origin' => AttendanceOrigin::MANUAL->value,
            'priority' => AttendancePriority::HIGH->value,
            'queue_id' => $queue->id,
        ])->assertCreated();

        $this->getJson('/api/v1/attendances?operational_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Carga recém-chegada');
    }

    public function test_it_invalidates_cached_operational_attendance_list_when_status_changes(): void
    {
        $user = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'supervisor',
            ]),
        );

        $queue = OperationQueue::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Operacao',
            'code' => 'OPE',
            'active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $user->tenant_id,
            'protocol' => 'AT-500012',
            'title' => 'Atendimento para resolução',
            'description' => 'Deve sair da fila operacional após a mudança de status.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::MANUAL,
            'priority' => AttendancePriority::MEDIUM,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'opened_at' => now(),
        ]);

        $this->getJson('/api/v1/attendances?operational_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->patchJson("/api/v1/attendances/{$attendance->id}/status", [
            'status' => AttendanceStatus::RESOLVED->value,
            'resolution_notes' => 'Tratativa concluída.',
        ])->assertOk();

        $this->getJson('/api/v1/attendances?operational_only=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_it_invalidates_cached_operational_attendance_list_when_assignment_changes(): void
    {
        $supervisor = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'supervisor',
            ]),
        );

        $assignee = User::query()->create([
            'tenant_id' => $supervisor->tenant_id,
            'name' => 'Operador Dois',
            'email' => 'operador2@example.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'is_active' => true,
        ]);

        $queue = OperationQueue::query()->create([
            'tenant_id' => $supervisor->tenant_id,
            'name' => 'Operacao',
            'code' => 'OPE',
            'active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $supervisor->tenant_id,
            'protocol' => 'AT-500013',
            'title' => 'Atendimento sem responsável',
            'description' => 'Deve refletir a nova atribuição após invalidar o cache.',
            'type' => AttendanceType::REQUEST,
            'origin' => AttendanceOrigin::PORTAL,
            'priority' => AttendancePriority::LOW,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'created_by' => $supervisor->id,
            'opened_at' => now(),
        ]);

        $this->getJson('/api/v1/attendances?operational_only=1')
            ->assertOk()
            ->assertJsonPath('data.0.assignee.id', null);

        $this->patchJson("/api/v1/attendances/{$attendance->id}/assignment", [
            'assigned_to' => $assignee->id,
        ])->assertOk();

        $this->getJson('/api/v1/attendances?operational_only=1')
            ->assertOk()
            ->assertJsonPath('data.0.assignee.id', $assignee->id)
            ->assertJsonPath('data.0.assignee.name', 'Operador Dois');
    }

    public function test_it_invalidates_cached_operational_attendance_list_when_queue_metadata_changes(): void
    {
        $admin = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'admin',
            ]),
        );

        $queue = OperationQueue::query()->create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Operacao',
            'code' => 'OPE',
            'active' => true,
        ]);

        Attendance::query()->create([
            'tenant_id' => $admin->tenant_id,
            'protocol' => 'AT-500014',
            'title' => 'Atendimento vinculado à fila',
            'description' => 'Deve refletir o novo nome da fila após a invalidação.',
            'type' => AttendanceType::INCIDENT,
            'origin' => AttendanceOrigin::MANUAL,
            'priority' => AttendancePriority::MEDIUM,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'created_by' => $admin->id,
            'opened_at' => now(),
        ]);

        $this->getJson('/api/v1/attendances?operational_only=1')
            ->assertOk()
            ->assertJsonPath('data.0.queue.name', 'Operacao');

        $this->patchJson("/api/v1/queues/{$queue->id}", [
            'name' => 'Operacao N1',
        ])->assertOk();

        $this->getJson('/api/v1/attendances?operational_only=1')
            ->assertOk()
            ->assertJsonPath('data.0.queue.name', 'Operacao N1');
    }
}
