<?php

namespace App\Enums;

enum VisitOutcome: string
{
    case Pending = 'pending';
    case Interested = 'interested';
    case Discarded = 'discarded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Interested => 'Le interesa',
            self::Discarded => 'Descartado',
        };
    }
}
