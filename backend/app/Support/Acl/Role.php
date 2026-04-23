<?php

namespace App\Support\Acl;

enum Role: string
{
    case Admin = 'admin';
    case Supervisor = 'supervisor';
    case Operator = 'operator';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador operacional',
            self::Supervisor => 'Supervisor de atendimento',
            self::Operator => 'Operador',
            self::Viewer => 'Leitor operacional',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Responsável por governança operacional, visão de ACL e coordenação do tenant.',
            self::Supervisor => 'Acompanha a operação, redistribui carga e conduz o fluxo do atendimento.',
            self::Operator => 'Atua na abertura e tratamento cotidiano dos atendimentos.',
            self::Viewer => 'Consulta a operação sem executar ações que alterem o fluxo.',
        };
    }
}
