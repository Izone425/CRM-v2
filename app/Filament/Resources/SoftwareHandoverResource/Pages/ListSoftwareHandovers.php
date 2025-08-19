<?php

namespace App\Filament\Resources\SoftwareHandoverResource\Pages;

use App\Filament\Resources\SoftwareHandoverResource;
use App\Filament\Resources\SoftwareResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSoftwareHandovers extends ListRecords
{
    protected static string $resource = SoftwareResource::class;
    protected static ?string $slug = 'software/project-list';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
