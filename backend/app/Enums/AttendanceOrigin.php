<?php

namespace App\Enums;

enum AttendanceOrigin: string
{
    case ERP = 'erp';
    case PDV = 'pdv';
    case PORTAL = 'portal';
    case API = 'api';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::ERP => 'ERP',
            self::PDV => 'PDV',
            self::PORTAL => 'Portal',
            self::API => 'API',
            self::MANUAL => 'Manual',
        };
    }
}
