<?php

namespace Tests\Unit;

use App\Enums\AttendanceOrigin;
use App\Enums\AttendancePriority;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Jobs\PropagateAttendanceToLegacy;
use App\Models\Attendance;
use App\Models\OperationQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropagateAttendanceToLegacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_the_processed_event_when_the_job_runs(): void
    {
        $user = $this->createUserForTenant();

        $queue = OperationQueue::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Integrações',
            'code' => 'INT',
            'active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'tenant_id' => $user->tenant_id,
            'protocol' => 'AT-610001',
            'title' => 'Sincronização pendente',
            'description' => 'Evento deve ser processado fora do request principal.',
            'type' => AttendanceType::INTEGRATION,
            'origin' => AttendanceOrigin::ERP,
            'priority' => AttendancePriority::HIGH,
            'status' => AttendanceStatus::OPEN,
            'queue_id' => $queue->id,
            'created_by' => $user->id,
            'opened_at' => now(),
        ]);

        config()->set('operations.attendance_integrations.connection', 'rabbitmq');
        config()->set('operations.attendance_integrations.queue', 'attendance-integrations');

        $job = new PropagateAttendanceToLegacy($attendance->id, $attendance->tenant_id, 'created');
        $job->handle();

        $this->assertDatabaseHas('attendance_events', [
            'attendance_id' => $attendance->id,
            'type' => 'legacy_sync_processed',
        ]);
    }
}
