<?php

namespace App\Support\Acl;

enum Permission: string
{
    case AclView = 'acl.view';
    case UsersView = 'users.view';
    case QueuesView = 'queues.view';
    case AttendancesView = 'attendances.view';
    case AttendancesCreate = 'attendances.create';
    case AttendancesUpdateStatus = 'attendances.update_status';
    case AttendancesAssign = 'attendances.assign';

    public function label(): string
    {
        return match ($this) {
            self::AclView => 'Visualizar matriz de ACL',
            self::UsersView => 'Visualizar operadores do tenant',
            self::QueuesView => 'Visualizar filas operacionais',
            self::AttendancesView => 'Visualizar atendimentos',
            self::AttendancesCreate => 'Abrir atendimentos',
            self::AttendancesUpdateStatus => 'Atualizar status de atendimentos',
            self::AttendancesAssign => 'Atribuir atendimentos',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AclView => 'Permite consultar os papéis iniciais e sua matriz de permissões.',
            self::UsersView => 'Permite listar usuários do mesmo tenant para contextos operacionais.',
            self::QueuesView => 'Permite consultar filas e indicadores operacionais.',
            self::AttendancesView => 'Permite visualizar lista, detalhe e histórico de atendimentos.',
            self::AttendancesCreate => 'Permite registrar novos atendimentos na fila operacional.',
            self::AttendancesUpdateStatus => 'Permite mover o atendimento entre estados do fluxo.',
            self::AttendancesAssign => 'Permite atribuir o atendimento a outro operador do tenant.',
        };
    }
}
