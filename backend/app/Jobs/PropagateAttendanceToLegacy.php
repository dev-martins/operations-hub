<?php

namespace App\Jobs;

use App\Models\Attendance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PropagateAttendanceToLegacy implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly int $attendanceId,
        public readonly int $tenantId,
        public readonly string $trigger,
    ) {
        $this->onConnection((string) config('operations.attendance_integrations.connection'));
        $this->onQueue((string) config('operations.attendance_integrations.queue'));
    }

    public function handle(): void
    {
        $attendance = Attendance::query()
            ->whereKey($this->attendanceId)
            ->where('tenant_id', $this->tenantId)
            ->first();

        if ($attendance === null) {
            return;
        }

        $attendance->events()->create([
            'tenant_id' => $attendance->tenant_id,
            'type' => 'legacy_sync_processed',
            'description' => 'Sincronização assíncrona com legado processada.',
            'metadata' => [
                'integration_trigger' => $this->trigger,
                'queue' => $this->queue,
                'connection' => $this->connection,
                'status' => $attendance->status->value,
                'processed_at' => now()->toIso8601String(),
            ],
            'created_by' => null,
            'created_at' => now(),
        ]);
    }
}
