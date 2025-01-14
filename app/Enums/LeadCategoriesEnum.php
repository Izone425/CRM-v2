<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeadCategoriesEnum: string implements HasLabel, HasColor
{
    case NEW = 'New';
    case ACTIVE = 'Active';
    case INACTIVE = 'Inactive';

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::NEW => '#FFA500',
            self::ACTIVE => '#00ff3e',
            self::INACTIVE => '#E5E4E2',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NEW => 'New',
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }
}
