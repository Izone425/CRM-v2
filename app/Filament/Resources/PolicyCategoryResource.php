<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PolicyCategoryResource\Pages;
use App\Models\PolicyCategory;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PolicyCategoryResource extends Resource
{
    protected static ?string $model = PolicyCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Policy Categories';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 27;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\CheckboxList::make('access_right')
                    ->label('Access Rights')
                    ->required()
                    ->helperText('Select which roles can access policies in this category')
                    ->options([
                        1 => 'Lead Owner',
                        2 => 'Salesperson',
                        3 => 'Manager',
                        4 => 'Implementer',
                        5 => 'Team Lead Implementer',
                        6 => 'Trainer',
                        7 => 'Team Lead Trainer',
                        8 => 'Support',
                        9 => 'Technician',
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('access_right')
                    ->label('Access Rights')
                    ->formatStateUsing(function ($state) {
                        // Handle case when $state is null or empty
                        if (empty($state)) {
                            return 'All Users';
                        }

                        // Convert to array if it's a string (JSON string)
                        if (is_string($state)) {
                            // If the string contains brackets like ["3","2"], try to parse it as JSON
                            if (strpos($state, '[') !== false) {
                                try {
                                    $state = json_decode($state, true);
                                } catch (\Exception $e) {
                                    return 'Invalid format';
                                }
                            }
                            // If it doesn't look like JSON, maybe it's a comma-separated list
                            else {
                                $state = explode(',', $state);
                            }
                        }

                        // If still not an array or is empty after conversion
                        if (!is_array($state) || empty($state)) {
                            return 'All Users';
                        }

                        $roleNames = [
                            1 => 'Lead Owner',
                            2 => 'Salesperson',
                            3 => 'Manager',
                            4 => 'Implementer',
                            5 => 'Team Lead Impl',
                            6 => 'Trainer',
                            7 => 'Team Lead Trainer',
                            8 => 'Support',
                            9 => 'Technician',
                        ];

                        $roles = [];
                        foreach ($state as $roleId) {
                            // Convert to int for consistent comparison
                            $roleIdInt = (int)$roleId;

                            if (array_key_exists($roleIdInt, $roleNames)) {
                                $roles[] = $roleNames[$roleIdInt];
                            } else {
                                $roles[] = "Role $roleId";
                            }
                        }

                        return !empty($roles) ? implode(', ', $roles) : 'All Users';
                    }),
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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'BENEFIT' => 'Employee HR Benefits',
                        'EXPENSE' => 'Expense Claims',
                    ]),
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
            'index' => Pages\ManagePolicyCategories::route('/'),
        ];
    }
}
