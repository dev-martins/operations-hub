<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function create(array $data, User $actor): Attendance
    {
        return DB::transaction(function () use ($data, $actor): Attendance {
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

            $attendance->events()->create([
                'tenant_id' => $attendance->tenant_id,
                'type' => 'created',
                'description' => 'Atendimento aberto na fila operacional.',
                'metadata' => [
                    'status' => $attendance->status->value,
                    'priority' => $attendance->priority->value,
                    'queue_id' => $attendance->queue_id,
                ],
                'created_by' => $actor->id,
                'created_at' => now(),
            ]);

            if ($attendance->assigned_to !== null) {
                $attendance->events()->create([
                    'tenant_id' => $attendance->tenant_id,
                    'type' => 'assigned',
                    'description' => 'Atendimento atribuído na abertura.',
                    'metadata' => [
                        'assigned_to' => $attendance->assigned_to,
                    ],
                    'created_by' => $actor->id,
                    'created_at' => now(),
                ]);
            }

            return $attendance->load(['queue', 'events', 'assignee', 'creator']);
        });
    }

    public function changeStatus(
        Attendance $attendance,
        AttendanceStatus $status,
        ?string $resolutionNotes = null,
        ?User $actor = null
    ): Attendance
    {
        return DB::transaction(function () use ($attendance, $status, $resolutionNotes, $actor): Attendance {
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

            $attendance->events()->create([
                'tenant_id' => $attendance->tenant_id,
                'type' => 'status_changed',
                'description' => 'Status do atendimento atualizado.',
                'metadata' => [
                    'status' => $status->value,
                    'resolution_notes' => $resolutionNotes,
                ],
                'created_by' => $actor?->id,
                'created_at' => now(),
            ]);

            return $attendance->fresh(['queue', 'events', 'assignee', 'creator']);
        });
    }

    public function assign(Attendance $attendance, int $assignedTo, ?User $actor = null): Attendance
    {
        return DB::transaction(function () use ($attendance, $assignedTo, $actor): Attendance {
            $attendance->update([
                'assigned_to' => $assignedTo,
            ]);

            $attendance->events()->create([
                'tenant_id' => $attendance->tenant_id,
                'type' => 'assigned',
                'description' => 'Responsável atribuído ao atendimento.',
                'metadata' => [
                    'assigned_to' => $assignedTo,
                ],
                'created_by' => $actor?->id,
                'created_at' => now(),
            ]);

            return $attendance->fresh(['queue', 'events', 'assignee', 'creator']);
        });
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
}
