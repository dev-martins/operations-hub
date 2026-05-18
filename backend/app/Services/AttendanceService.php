<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Jobs\PropagateAttendanceToLegacy;
use App\Models\Attendance;
use App\Models\User;
use App\Support\Cache\OperationalAttendanceListCache;
use App\Support\Cache\QueueOverviewCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        private readonly QueueOverviewCache $queueOverviewCache,
        private readonly OperationalAttendanceListCache $operationalAttendanceListCache,
    ) {}

    public function create(array $data, User $actor): Attendance
    {
        $attendance = DB::transaction(function () use ($data, $actor): Attendance {
            $attendance = Attendance::create([
                'tenant_id' => $actor->tenant_id,
                'protocol' => $this->generateProtocol(),
                'title' => $data['title'],
                'description' => $data['description'],
                'type' => $data['type'],
                'origin' => $data['origin'],
                'priority' => $data['priority'],
                'status' => AttendanceStatus::OPEN,
                'queue_id' => $data['queue_id'],
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => $actor->id,
                'opened_at' => now(),
            ]);

            $this->registerEvent(
                $attendance,
                'created',
                'Atendimento aberto na fila operacional.',
                [
                    'status' => $attendance->status->value,
                    'priority' => $attendance->priority->value,
                    'queue_id' => $attendance->queue_id,
                ],
                $actor,
            );

            if ($attendance->assigned_to !== null) {
                $this->registerEvent(
                    $attendance,
                    'assigned',
                    'Atendimento atribuído na abertura.',
                    [
                        'assigned_to' => $attendance->assigned_to,
                    ],
                    $actor,
                );
            }

            if ($this->attendanceIntegrationEnabled()) {
                $this->registerEvent(
                    $attendance,
                    'legacy_sync_requested',
                    'Sincronização assíncrona com legado enfileirada.',
                    [
                        'integration_trigger' => 'created',
                        'queue' => config('operations.attendance_integrations.queue'),
                        'connection' => config('operations.attendance_integrations.connection'),
                    ],
                    $actor,
                );

                PropagateAttendanceToLegacy::dispatch(
                    $attendance->id,
                    $attendance->tenant_id,
                    'created',
                )->afterCommit();
            }

            return $attendance->load(['queue', 'events', 'assignee', 'creator']);
        });

        $this->queueOverviewCache->forgetForTenant($actor->tenant_id);
        $this->operationalAttendanceListCache->invalidateForTenant($actor->tenant_id);

        return $attendance;
    }

    public function changeStatus(
        Attendance $attendance,
        AttendanceStatus $status,
        ?string $resolutionNotes = null,
        ?User $actor = null
    ): Attendance {
        $attendance = DB::transaction(function () use ($attendance, $status, $resolutionNotes, $actor): Attendance {
            $this->ensureStatusTransitionIsMeaningful($attendance, $status);

            $payload = [
                'status' => $status,
            ];

            if ($status === AttendanceStatus::IN_PROGRESS && $attendance->first_response_at === null) {
                $payload['first_response_at'] = now();
            }

            if ($status === AttendanceStatus::RESOLVED) {
                $payload['resolved_at'] = now();
                $payload['resolution_notes'] = $resolutionNotes;
            }

            $attendance->update($payload);

            $this->registerEvent(
                $attendance,
                'status_changed',
                'Status do atendimento atualizado.',
                [
                    'status' => $status->value,
                    'resolution_notes' => $resolutionNotes,
                ],
                $actor,
            );

            return $attendance->fresh(['queue', 'events', 'assignee', 'creator']);
        });

        $this->queueOverviewCache->forgetForTenant($attendance->tenant_id);
        $this->operationalAttendanceListCache->invalidateForTenant($attendance->tenant_id);

        return $attendance;
    }

    public function assign(Attendance $attendance, int $assignedTo, ?User $actor = null): Attendance
    {
        $attendance = DB::transaction(function () use ($attendance, $assignedTo, $actor): Attendance {
            $attendance->update([
                'assigned_to' => $assignedTo,
            ]);

            $this->registerEvent(
                $attendance,
                'assigned',
                'Responsável atribuído ao atendimento.',
                [
                    'assigned_to' => $assignedTo,
                ],
                $actor,
            );

            return $attendance->fresh(['queue', 'events', 'assignee', 'creator']);
        });

        $this->operationalAttendanceListCache->invalidateForTenant($attendance->tenant_id);

        return $attendance;
    }

    private function generateProtocol(): string
    {
        return sprintf('AT-%s', str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT));
    }

    private function ensureStatusTransitionIsMeaningful(Attendance $attendance, AttendanceStatus $status): void
    {
        if ($attendance->status === $status) {
            throw ValidationException::withMessages([
                'status' => 'O atendimento já está no status informado.',
            ]);
        }
    }

    private function attendanceIntegrationEnabled(): bool
    {
        return (bool) config('operations.attendance_integrations.enabled');
    }

    private function registerEvent(
        Attendance $attendance,
        string $type,
        string $description,
        array $metadata = [],
        ?User $actor = null,
    ): void {
        $attendance->events()->create([
            'tenant_id' => $attendance->tenant_id,
            'type' => $type,
            'description' => $description,
            'metadata' => $metadata,
            'created_by' => $actor?->id,
            'created_at' => now(),
        ]);
    }
}
