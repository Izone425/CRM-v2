<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeadStatusEnum: string implements HasLabel
{
    case NONE = 'None';
    case NEW = 'New';
    case RFQ_TRANSFER = 'RFQ-Transfer';
    case PENDING_DEMO = 'Pending Demo';
    case UNDER_REVIEW = 'Under Review';
    case DEMO_CANCELLED = 'Demo Cancelled';
    case DEMO_ASSIGNED = 'Demo-Assigned';
    case RFQ_FOLLOW_UP = 'RFQ-Follow Up';
    case HOT = 'Hot';
    case WARM = 'Warm';
    case COLD = 'Cold';
    case JUNK = 'Junk';
    case ON_HOLD = 'On Hold';
    case LOST = 'Lost';
    case NO_RESPONSE = 'No Response';
    case CLOSED = 'Closed';

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::NONE => '#ffe1a5',
            self::NEW => '#ffe1a5',
            self::RFQ_TRANSFER => '#ffe1a5',
            self::PENDING_DEMO => '#ffe1a5',
            self::UNDER_REVIEW => '#ffe1a5',
            self::DEMO_CANCELLED => '#ffe1a5',
            self::DEMO_ASSIGNED => '#ffffa5',
            self::RFQ_FOLLOW_UP => '#431fa19c',
            self::HOT => '#ff0000a1',
            self::WARM => '#ffa5008f',
            self::COLD => '#00e7ff',
            self::JUNK => '#E5E4E2',
            self::ON_HOLD => '#E5E4E2',
            self::LOST => '#E5E4E2',
            self::NO_RESPONSE => '#E5E4E2',
            self::CLOSED => '#ffe1a5',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NONE => 'None',
            self::NEW => 'New',
            self::RFQ_TRANSFER => 'RFQ-Transfer',
            self::PENDING_DEMO => 'Pending Demo',
            self::UNDER_REVIEW => 'Under Review',
            self::DEMO_CANCELLED => 'Demo Cancelled',
            self::DEMO_ASSIGNED => 'Demo-Assigned',
            self::RFQ_FOLLOW_UP => 'RFQ-Follow Up',
            self::HOT => 'Hot',
            self::WARM => 'Warm',
            self::COLD => 'Cold',
            self::JUNK => 'Junk',
            self::ON_HOLD => 'On Hold',
            self::LOST => 'Lost',
            self::NO_RESPONSE => 'No Response',
            self::CLOSED => 'Closed',
        };
    }
}
