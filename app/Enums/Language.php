<?php

declare(strict_types=1);

namespace App\Enums;

enum Language: string
{
    case Bm = 'bm';
    case En = 'en';

    public function label(): string
    {
        return match ($this) {
            self::Bm => 'BM',
            self::En => 'EN',
        };
    }

    public function fullName(): string
    {
        return match ($this) {
            self::Bm => 'Bahasa Melayu',
            self::En => 'English',
        };
    }
}
