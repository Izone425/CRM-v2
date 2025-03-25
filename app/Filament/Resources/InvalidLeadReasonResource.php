<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvalidLeadReasonResource\Pages\ManageInvalidLeadReason;
use App\Models\InvalidLeadReason;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class InvalidLeadReasonResource extends Resource
{
    protected static ?string $model = InvalidLeadReason::class;
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationIcon = 'heroicon-o-x-circle';
    protected static ?string $navigationLabel = 'Invalid Lead Reasons';

    public static function canAccess(): bool
    {
        return auth()->user()->role_id == '3';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('lead_stage')
                ->label('Lead Status') // Optional: override label if needed
                ->required()
                ->options([
                    'Closed' => 'Closed',
                    'Junk' => 'Junk',
                    'On Hold' => 'On Hold',
                    'Lost' => 'Lost',
                ]),
            TextInput::make('reason')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lead_stage')->sortable()->searchable(),
                TextColumn::make('reason')->sortable()->searchable(),
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
            'index' => ManageInvalidLeadReason::route('/'),
        ];
    }
}
