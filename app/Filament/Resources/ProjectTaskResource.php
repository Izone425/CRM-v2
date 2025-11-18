<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectTaskResource\Pages;
use App\Models\ProjectTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectTaskResource extends Resource
{
    protected static ?string $model = ProjectTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Project Management';

    protected static ?string $navigationLabel = 'Project Task Templates';

    public static function form(Form $form): Form
    {
        // Check if we're in edit mode
        $isEdit = $form->getOperation() === 'edit';

        if ($isEdit) {
            return $form->schema(self::getEditSchema());
        }

        return $form->schema(self::getCreateSchema());
    }

    protected static function getCreateSchema(): array
    {
        return [
            Forms\Components\Section::make('Module Information')
                ->description('Define the module details that will be used across multiple tasks')
                ->schema([
                    Forms\Components\Select::make('module')
                        ->label('Module Code')
                        ->required()
                        ->searchable()
                        ->options(function (callable $get) {
                            $hrVersion = $get('hr_version') ?? '1';

                            if ($hrVersion == '1') {
                                return [
                                    'attendance' => 'Attendance',
                                    'leave' => 'Leave',
                                    'claim' => 'Claim',
                                    'payroll' => 'Payroll',
                                ];
                            }

                            // Version 2 modules (you can customize these)
                            return [
                                'attendance' => 'Attendance',
                                'leave' => 'Leave',
                                'claim' => 'Claim',
                                'payroll' => 'Payroll',
                            ];
                        })
                        ->reactive(),

                    Forms\Components\Select::make('hr_version')
                        ->label('HR Version')
                        ->options([
                            '1' => 'Version 1',
                            '2' => 'Version 2',
                        ])
                        ->required()
                        ->default('1')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            // Auto-calculate next module_order based on HR version
                            $latestOrder = \App\Models\ProjectTask::where('hr_version', $state)
                                ->max('module_order') ?? 0;
                            $set('module_order', $latestOrder + 1);
                        }),

                    Forms\Components\TextInput::make('module_name')
                        ->label('Module Display Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('module_order')
                        ->label('Module Order')
                        ->numeric()
                        ->default(function (callable $get) {
                            $hrVersion = $get('hr_version') ?? '1';
                            $latestOrder = \App\Models\ProjectTask::where('hr_version', $hrVersion)
                                ->max('module_order') ?? 0;
                            return $latestOrder + 1;
                        })
                        ->required(),

                    Forms\Components\TextInput::make('module_percentage')
                        ->label('Module Weight (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(0)
                        ->required(),
                ])
                ->columns(5)
                ->collapsible(),

            Forms\Components\Section::make('Tasks')
                ->description('Add multiple tasks for this module')
                ->schema([
                    Forms\Components\Repeater::make('tasks')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('task_name')
                                ->label('Task Name')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('task_percentage')
                                ->label('Task Weight (%)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->default(0)
                                ->required(),

                            Forms\Components\TextInput::make('order')
                                ->label('Sort Order')
                                ->numeric()
                                ->default(0)
                                ->required(),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->inline(false),
                        ])
                        ->columns(5)
                        ->defaultItems(1)
                        ->minItems(1)
                        ->required()
                        ->addActionLabel('Add Another Task')
                        ->itemLabel(fn (array $state): ?string => $state['task_name'] ?? 'New Task')
                        ->cloneable()
                        ->collapsible()
                        ->deleteAction(
                            fn (Forms\Components\Actions\Action $action) => $action
                                ->requiresConfirmation()
                        ),
                ])
                ->collapsible(),
        ];
    }

    protected static function getEditSchema(): array
    {
        return [
            Forms\Components\Section::make('Module Information')
                ->description('Module details - Note: Changing module code will affect all tasks using this module')
                ->schema([
                    Forms\Components\TextInput::make('module')
                        ->label('Module Code')
                        ->required()
                        ->maxLength(255)
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('module', strtolower($state))),

                    Forms\Components\TextInput::make('module_order')
                        ->label('Module Order')
                        ->helperText('Lower numbers appear first')
                        ->numeric()
                        ->required(),

                    Forms\Components\Select::make('hr_version')
                        ->label('HR Version')
                        ->options([
                            '1' => 'Version 1',
                            '2' => 'Version 2',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('module_name')
                        ->label('Module Display Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('module_percentage')
                        ->label('Module Weight (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),
                ])
                ->columns(3)
                ->collapsible(),

            Forms\Components\Section::make('Task Information')
                ->description('Edit this specific task')
                ->schema([
                    Forms\Components\TextInput::make('task_name')
                        ->label('Task Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('task_percentage')
                        ->label('Task Weight (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),

                    Forms\Components\TextInput::make('order')
                        ->label('Sort Order')
                        ->numeric()
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(3)
                ->collapsible(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('module')
                    ->label('Module Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('module_name')
                    ->label('Module Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('module_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('module_percentage')
                    ->label('Module %')
                    ->sortable()
                    ->suffix('%')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('hr_version')
                    ->label('HR Version')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'warning',
                        '2' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => "v{$state}"),

                Tables\Columns\TextColumn::make('task_name')
                    ->label('Task')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('task_percentage')
                    ->label('Task %')
                    ->sortable()
                    ->suffix('%')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('order')
                    ->label('Task Order')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order')
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->label('Module')
                    ->options(fn () => ProjectTask::select('module', 'module_name')
                        ->distinct()
                        ->pluck('module_name', 'module')
                        ->toArray()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Tasks')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
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
            'index' => Pages\ListProjectTasks::route('/'),
            'create' => Pages\CreateProjectTask::route('/create'),
            'edit' => Pages\EditProjectTask::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}
