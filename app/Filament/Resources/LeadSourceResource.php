<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadSourceResource\Pages;
use App\Models\LeadSource;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;

class LeadSourceResource extends Resource
{
    protected static ?string $model = LeadSource::class;
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Lead Sources';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.resources.lead-sources.index');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('lead_code')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lead_code')->sortable()->searchable(),
            ])
            ->actions([
                EditAction::make()
                    ->closeModalByClickingAway(false),
                DeleteAction::make()
                    ->closeModalByClickingAway(false),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLeadSources::route('/'),
        ];
    }
}
