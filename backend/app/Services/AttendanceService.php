<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function create(array $data): Attendance
    {
        return DB::transaction(function () use ($data): Attendance {
            $attendance = Attendance::create([
                'tenant_id' => $data['tenant_id'] ?? 1,
                'protocol' => $this->generateProtocol(),
                'title' => $data['title'],
                'description' => $data['description'],
                'type' => $data['type'],
                'origin' => $data['origin'],
                'priority' => $data['priority'],
                'status' => AttendanceStatus::OPEN,
                'queue_id' => $data['queue_id'],
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => $data['created_by'] ?? null,
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
                'created_by' => $attendance->created_by,
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
                    'created_by' => $attendance->created_by,
                    'created_at' => now(),
                ]);
            }

            return $attendance->load(['queue', 'events']);
        });
    }

    public function changeStatus(Attendance $attendance, AttendanceStatus $status, ?string $resolutionNotes = null): Attendance
    {
        return DB::transaction(function () use ($attendance, $status, $resolutionNotes): Attendance {
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
                'created_by' => $attendance->created_by,
                'created_at' => now(),
            ]);

            return $attendance->fresh(['queue', 'events']);
        });
    }

    public function assign(Attendance $attendance, int $assignedTo): Attendance
    {
        return DB::transaction(function () use ($attendance, $assignedTo): Attendance {
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
                'created_by' => $attendance->created_by,
                'created_at' => now(),
            ]);

            return $attendance->fresh(['queue', 'events']);
        });
    }

    private function generateProtocol(): string
    {
        return sprintf('AT-%s', str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT));
    }
}
