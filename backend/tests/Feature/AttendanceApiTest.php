<?php

namespace Tests\Feature;

use App\Enums\AttendanceOrigin;
use App\Enums\AttendancePriority;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\OperationQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_attendance_and_registers_the_first_event(): void
    {
        $queue = OperationQueue::query()->create([
            'tenant_id' => 1,
            'name' => 'Integrações',
            'code' => 'INT',
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/attendances', [
            'tenant_id' => 1,
            'title' => 'Falha de sincronização com ERP',
            'description' => 'O payload de faturamento não retornou confirmação.',
            'type' => AttendanceType::INTEGRATION->value,
            'origin' => AttendanceOrigin::ERP->value,
            'priority' => AttendancePriority::HIGH->value,
            'queue_id' => $queue->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Falha de sincronização com ERP')
            ->assertJsonPath('data.status', AttendanceStatus::OPEN->value)
            ->assertJsonPath('data.queue.code', 'INT');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('attendance_events', 1);
        $this->assertDatabaseHas('attendance_events', [
            'type' => 'created',
        ]);
    }

    public function test_it_validates_required_fields_when_creating_an_attendance(): void
    {
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

    public function test_it_lists_attendances_with_queue_information(): void
    {
        $queue = OperationQueue::query()->create([
            'tenant_id' => 1,
            'name' => 'Suporte N1',
            'code' => 'SUP-N1',
            'active' => true,
        ]);

        Attendance::query()->create([
            'tenant_id' => 1,
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

        $response = $this->getJson('/api/v1/attendances');

        $response->assertOk()
            ->assertJsonPath('data.0.protocol', 'AT-500001')
            ->assertJsonPath('data.0.queue.code', 'SUP-N1');
    }

    public function test_it_shows_a_single_attendance_with_events(): void
    {
        $queue = OperationQueue::query()->create([
            'tenant_id' => 1,
            'name' => 'Financeiro',
            'code' => 'FIN',
            'active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => 1,
            'protocol' => 'AT-500002',
            'title' => 'Divergência de pagamento',
            'description' => 'Pagamento aprovado sem baixa no ERP.',
            'type' => AttendanceType::FINANCIAL,
            'origin' => AttendanceOrigin::PDV,
            'priority' => AttendancePriority::CRITICAL,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'opened_at' => now(),
        ]);

        $attendance->events()->create([
            'tenant_id' => 1,
            'type' => 'created',
            'description' => 'Atendimento criado.',
            'created_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/attendances/{$attendance->id}");

        $response->assertOk()
            ->assertJsonPath('data.protocol', 'AT-500002')
            ->assertJsonPath('data.events.0.type', 'created');
    }

    public function test_it_changes_status_and_requires_resolution_notes_when_resolving(): void
    {
        $queue = OperationQueue::query()->create([
            'tenant_id' => 1,
            'name' => 'Críticos',
            'code' => 'CRT',
            'active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => 1,
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
        ]);
    }

    public function test_it_assigns_an_attendance_to_a_user(): void
    {
        $queue = OperationQueue::query()->create([
            'tenant_id' => 1,
            'name' => 'Suporte N1',
            'code' => 'SUP-N1',
            'active' => true,
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::query()->create([
            'tenant_id' => 1,
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
            'assigned_to' => $user->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.assigned_to', $user->id);

        $this->assertDatabaseHas('attendance_events', [
            'attendance_id' => $attendance->id,
            'type' => 'assigned',
        ]);
    }
}
