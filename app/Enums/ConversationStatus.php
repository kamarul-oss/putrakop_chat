<?php

declare(strict_types=1);

namespace App\Enums;

enum ConversationStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Active = 'active';
    case Transferred = 'transferred';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Queued => 'Queued',
            self::Active => 'Active',
            self::Transferred => 'Transferred',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Queued => 'blue',
            self::Active => 'green',
            self::Transferred => 'orange',
            self::Closed => 'gray',
        };
    }
}
