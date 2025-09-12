<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstallerResource\Pages;
use App\Filament\Resources\InstallerResource\RelationManagers;
use App\Models\Installer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstallerResource extends Resource
{
    protected static ?string $model = Installer::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Settings';

    // public static function canAccess(): bool
    // {
    //     $user = auth()->user();

    //     // Allow access if user has role_id 3 OR if user's ID is 20
    //     return $user->role_id == '3' || $user->id == 20;
    // }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.resources.installers.index');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('company_name')
                ->label('Company Name')
                ->required()
                ->maxLength(255)
                ->extraAlpineAttributes([
                    'x-on:input' => '
                        const start = $el.selectionStart;
                        const end = $el.selectionEnd;
                        const value = $el.value;
                        $el.value = value.toUpperCase();
                        $el.setSelectionRange(start, end);
                    '
                ])
                ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // No filters needed for simple implementation
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstallers::route('/'),
            'create' => Pages\CreateInstaller::route('/create'),
            'edit' => Pages\EditInstaller::route('/{record}/edit'),
        ];
    }
}
