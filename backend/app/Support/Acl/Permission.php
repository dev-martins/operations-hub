<?php

namespace App\Support\Acl;

enum Permission: string
{
    case AclView = 'acl.view';
    case AclManage = 'acl.manage';
    case UsersView = 'users.view';
    case QueuesView = 'queues.view';
    case QueuesManage = 'queues.manage';
    case AttendancesView = 'attendances.view';
    case AttendancesCreate = 'attendances.create';
    case AttendancesUpdateStatus = 'attendances.update_status';
    case AttendancesResolve = 'attendances.resolve';
    case AttendancesCancel = 'attendances.cancel';
    case AttendancesAssign = 'attendances.assign';

    public function label(): string
    {
        return match ($this) {
            self::AclView => 'Visualizar matriz de ACL',
            self::AclManage => 'Gerenciar papéis do tenant',
            self::UsersView => 'Visualizar operadores do tenant',
            self::QueuesView => 'Visualizar filas operacionais',
            self::QueuesManage => 'Gerenciar filas operacionais',
            self::AttendancesView => 'Visualizar atendimentos',
            self::AttendancesCreate => 'Abrir atendimentos',
            self::AttendancesUpdateStatus => 'Atualizar status de atendimentos',
            self::AttendancesResolve => 'Resolver atendimentos',
            self::AttendancesCancel => 'Cancelar atendimentos',
            self::AttendancesAssign => 'Atribuir atendimentos',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AclView => 'Permite consultar os papéis iniciais e sua matriz de permissões.',
            self::AclManage => 'Permite alterar papéis de usuários do tenant dentro da governança administrativa.',
            self::UsersView => 'Permite listar usuários do mesmo tenant para contextos operacionais.',
            self::QueuesView => 'Permite consultar filas e indicadores operacionais.',
            self::QueuesManage => 'Permite criar e atualizar filas operacionais do tenant.',
            self::AttendancesView => 'Permite visualizar lista, detalhe e histórico de atendimentos.',
            self::AttendancesCreate => 'Permite registrar novos atendimentos na fila operacional.',
            self::AttendancesUpdateStatus => 'Permite mover o atendimento entre estados do fluxo.',
            self::AttendancesResolve => 'Permite concluir formalmente o atendimento com registro de resolução.',
            self::AttendancesCancel => 'Permite cancelar atendimentos quando o fluxo não deve seguir.',
            self::AttendancesAssign => 'Permite atribuir o atendimento a outro operador do tenant.',
        };
    }
}
