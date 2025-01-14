<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeadDetailRelationManager extends RelationManager
{
    protected static string $relationship = 'leadSource';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('lead_code')
            ->columns([
                Tables\Columns\TextColumn::make('lead_code'),
                Tables\Columns\TextColumn::make('salesperson'),
                Tables\Columns\TextColumn::make('platform'),
            ]);
    }
}
