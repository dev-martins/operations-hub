<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case WAITING_EXTERNAL = 'waiting_external';
    case RESOLVED = 'resolved';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Aberto',
            self::IN_PROGRESS => 'Em atendimento',
            self::WAITING_EXTERNAL => 'Aguardando externo',
            self::RESOLVED => 'Resolvido',
            self::CANCELLED => 'Cancelado',
        };
    }
}
