<?php

namespace App\Enums;

enum AttendanceType: string
{
    case INCIDENT = 'incident';
    case REQUEST = 'request';
    case INTEGRATION = 'integration';
    case FINANCIAL = 'financial';

    public function label(): string
    {
        return match ($this) {
            self::INCIDENT => 'Incidente',
            self::REQUEST => 'Solicitação',
            self::INTEGRATION => 'Integração',
            self::FINANCIAL => 'Financeiro',
        };
    }
}
