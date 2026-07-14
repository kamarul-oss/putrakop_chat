<?php

declare(strict_types=1);

namespace App\Enums;

enum AgentStatus: string
{
    case Online = 'online';
    case Away = 'away';
    case Busy = 'busy';
    case Offline = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Away => 'Away',
            self::Busy => 'Busy',
            self::Offline => 'Offline',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Online => 'green',
            self::Away => 'yellow',
            self::Busy => 'red',
            self::Offline => 'gray',
        };
    }
}
