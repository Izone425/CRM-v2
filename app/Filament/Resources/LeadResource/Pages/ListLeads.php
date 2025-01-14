<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\Customer;
use App\Models\PipelineStage;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Leads'),
            'active' => Tab::make('Active')->query(fn ($query) => $query->where('categories', 'Active')),
            'transfer' => Tab::make('Transfer')->query(fn ($query) => $query->where('stage', 'Transfer')),
            'demo' => Tab::make('Demo')->query(fn ($query) => $query->where('stage', 'Demo')),
            'follow_up' => Tab::make('Follow Up')->query(fn ($query) => $query->where('stage', 'Follow Up')),
            'inactive' => Tab::make('Inactive')->query(fn ($query) => $query->where('categories', 'Inactive')),
        ];
    }
}
