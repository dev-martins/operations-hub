<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\OperationQueue;
use Illuminate\Database\Seeder;

class OperationQueueSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = Tenant::query()->value('id') ?? 1;

        $queues = [
            [
                'tenant_id' => $tenantId,
                'name' => 'Suporte N1',
                'code' => 'SUP-N1',
                'description' => 'Fila para triagem inicial de incidentes operacionais.',
                'active' => true,
            ],
            [
                'tenant_id' => $tenantId,
                'name' => 'Financeiro',
                'code' => 'FIN',
                'description' => 'Fila para ocorrências de pagamento, faturamento e bloqueios.',
                'active' => true,
            ],
            [
                'tenant_id' => $tenantId,
                'name' => 'Integrações',
                'code' => 'INT',
                'description' => 'Fila para falhas de sincronização e reprocessamentos.',
                'active' => true,
            ],
            [
                'tenant_id' => $tenantId,
                'name' => 'Críticos',
                'code' => 'CRT',
                'description' => 'Fila para demandas com risco alto e SLA reduzido.',
                'active' => true,
            ],
        ];

        foreach ($queues as $queue) {
            OperationQueue::query()->updateOrCreate(
                ['tenant_id' => $queue['tenant_id'], 'code' => $queue['code']],
                $queue,
            );
        }
    }
}
