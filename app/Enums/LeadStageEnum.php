<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeadStageEnum: string implements HasLabel
{
    case NEW = 'New';
    case TRANSFER = 'Transfer';
    case DEMO = 'Demo';
    case FOLLOW_UP = 'Follow Up';

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::NEW => '#ffe1a5',
            self::FOLLOW_UP => '#adffb7',
            self::TRANSFER => '#ffe1a5',
            self::DEMO => '#ffffa5',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NEW => 'New',
            self::TRANSFER => 'Transfer',
            self::DEMO => 'Demo',
            self::FOLLOW_UP => 'Follow Up',
        };
    }
}
