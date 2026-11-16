<?php

namespace App\Enums;

enum PropertyType: string
{
    case Flat = 'flat';
    case House = 'house';
    case Commercial = 'commercial';
    case Office = 'office';

    public function label(): string
    {
        return match ($this) {
            self::Flat => 'Piso',
            self::House => 'Casa',
            self::Commercial => 'Local',
            self::Office => 'Oficina',
        };
    }
}
