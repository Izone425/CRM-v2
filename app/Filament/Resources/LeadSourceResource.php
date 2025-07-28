<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadSourceResource\Pages;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
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

            Select::make('allowed_users')
                ->multiple()
                ->label('Users with access')
                ->options(function () {
                    // Create option groups with headers
                    $options = [];

                    // Add Managers (role_id 3)
                    $managers = User::where('role_id', 3)
                        ->orderBy('name')
                        ->get();

                    if ($managers->count() > 0) {
                        $options['Managers'] = $managers->pluck('name', 'id')->toArray();
                    }

                    // Add HR Sales (specific user IDs)
                    $hrSales = User::whereIn('id', [6, 7, 8, 9, 10, 11, 12])
                        ->orderBy('name')
                        ->get();

                    if ($hrSales->count() > 0) {
                        $options['HR Sales'] = $hrSales->pluck('name', 'id')->toArray();
                    }

                    // Add Other Salespeople (role_id 2, excluding HR sales IDs)
                    $otherSales = User::where('role_id', 2)
                        ->whereNotIn('id', [6, 7, 8, 9, 10, 11, 12])
                        ->orderBy('name')
                        ->get();

                    if ($otherSales->count() > 0) {
                        $options['Other Sales'] = $otherSales->pluck('name', 'id')->toArray();
                    }

                    // Add Lead Owners (role_id 1)
                    $leadOwners = User::where('role_id', 1)
                        ->orderBy('name')
                        ->get();

                    if ($leadOwners->count() > 0) {
                        $options['Lead Owners'] = $leadOwners->pluck('name', 'id')->toArray();
                    }

                    return $options;
                })
                ->afterStateHydrated(function (Select $component, $state, ?LeadSource $record) {
                    if ($record && $record->allowed_users) {
                        // Get the allowed_users array - it should already be decoded thanks to the cast
                        $allowedUsers = is_array($record->allowed_users) ? $record->allowed_users : json_decode($record->allowed_users, true);

                        // Convert string IDs to integers for the select component
                        if (is_array($allowedUsers)) {
                            $allowedUsers = array_map('intval', $allowedUsers);
                            $component->state($allowedUsers);
                        }
                    }
                })
                ->dehydrateStateUsing(function ($state) {
                    // Convert integer IDs back to strings for storage
                    if (is_array($state)) {
                        return array_map('strval', $state);
                    }
                    return $state;
                })
                ->preload()
                ->searchable()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('lead_code') // Add this line to sort by lead_code alphabetically (A to Z)
            ->columns([
                TextColumn::make('lead_code')
                    ->sortable()
                    ->searchable(),

                // TextColumn::make('allowed_users')
                //     ->label('Authorized Users')
                //     ->formatStateUsing(function ($state) {
                //         // Handle if the state is null
                //         if (!$state) {
                //             return 'No users';
                //         }

                //         // Get user names for the selected IDs
                //         $userIds = is_array($state) ? $state : json_decode($state, true);
                //         $users = User::whereIn('id', $userIds)->pluck('name')->toArray();

                //         return implode(', ', $users);
                //     }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make()
                    ->closeModalByClickingAway(false),
                DeleteAction::make()
                    ->closeModalByClickingAway(false)
                    ->hidden(function (LeadSource $record): bool {
                        return Lead::where('lead_code', $record->lead_code)->exists();
                    })
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLeadSources::route('/'),
        ];
    }
}
