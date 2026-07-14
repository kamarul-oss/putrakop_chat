<?php

declare(strict_types=1);

namespace App\Enums;

enum SenderType: string
{
    case Customer = 'customer';
    case Agent = 'agent';
    case Ai = 'ai';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Agent => 'Agent',
            self::Ai => 'AI',
            self::System => 'System',
        };
    }
}
