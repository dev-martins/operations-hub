<?php

namespace Database\Seeders;

use App\Enums\AttendanceOrigin;
use App\Enums\AttendancePriority;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\OperationQueue;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate([
            'slug' => 'montreal-operacoes',
        ], [
            'name' => 'Montreal Operacoes',
            'active' => true,
        ]);

        $user = User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'tenant_id' => $tenant->id,
            'name' => 'Test User',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $user->forceFill([
            'tenant_id' => $tenant->id,
            'role' => $user->role ?: 'admin',
        ])->save();

        $this->call([
            PassportClientSeeder::class,
            OperationQueueSeeder::class,
        ]);

        if (Attendance::query()->exists()) {
            return;
        }

        $queues = OperationQueue::query()->pluck('id', 'code');

        $attendances = [
            [
                'protocol' => 'AT-100001',
                'title' => 'Webhook de cobrança sem retorno',
                'description' => 'Integração financeira não confirmou o processamento do webhook.',
                'type' => AttendanceType::INTEGRATION,
                'origin' => AttendanceOrigin::API,
                'priority' => AttendancePriority::CRITICAL,
                'status' => AttendanceStatus::OPEN,
                'queue_id' => $queues['INT'],
            ],
            [
                'protocol' => 'AT-100002',
                'title' => 'Fila de aprovação com atraso',
                'description' => 'Aprovações pendentes acumuladas na operação financeira.',
                'type' => AttendanceType::FINANCIAL,
                'origin' => AttendanceOrigin::ERP,
                'priority' => AttendancePriority::HIGH,
                'status' => AttendanceStatus::IN_PROGRESS,
                'queue_id' => $queues['FIN'],
            ],
            [
                'protocol' => 'AT-100003',
                'title' => 'Reprocessamento de evento legado',
                'description' => 'Evento de integração precisa ser reenviado para o legado.',
                'type' => AttendanceType::INTEGRATION,
                'origin' => AttendanceOrigin::ERP,
                'priority' => AttendancePriority::MEDIUM,
                'status' => AttendanceStatus::WAITING_EXTERNAL,
                'queue_id' => $queues['CRT'],
            ],
        ];

        foreach ($attendances as $attendance) {
            $record = Attendance::query()->create([
                'tenant_id' => 1,
                'protocol' => $attendance['protocol'],
                'title' => $attendance['title'],
                'description' => $attendance['description'],
                'type' => $attendance['type'],
                'origin' => $attendance['origin'],
                'priority' => $attendance['priority'],
                'status' => $attendance['status'],
                'queue_id' => $attendance['queue_id'],
                'assigned_to' => $user->id,
                'created_by' => $user->id,
                'opened_at' => now()->subMinutes(random_int(10, 90)),
                'first_response_at' => $attendance['status'] !== AttendanceStatus::OPEN ? now()->subMinutes(random_int(5, 30)) : null,
            ]);

            $record->events()->create([
                'tenant_id' => 1,
                'type' => 'created',
                'description' => 'Atendimento gerado pelo seeder inicial.',
                'metadata' => [
                    'source' => 'database_seeder',
                ],
                'created_by' => $user->id,
                'created_at' => now(),
            ]);
        }
    }
}
