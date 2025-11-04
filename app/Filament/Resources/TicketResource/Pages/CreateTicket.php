<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;
    protected static bool $canCreateAnother = false;

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
