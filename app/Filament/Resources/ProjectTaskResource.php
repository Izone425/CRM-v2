<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ProjectTaskResource\Pages;
use App\Models\ProjectTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectTaskResource extends Resource
{
    protected static ?string $model = ProjectTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Project Management';

    protected static ?string $navigationLabel = 'Task Templates';

    protected static ?string $modelLabel = 'Task Template';

    protected static ?string $pluralModelLabel = 'Task Templates';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('module')
                    ->options(ProjectTask::getModules())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $set('module_order', ProjectTask::getModuleOrder($state));
                    }),
                Forms\Components\TextInput::make('module_order')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->helperText('Order of this module in the project flow'),
                Forms\Components\TextInput::make('phase_name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('e.g., Kickoff, Setup, Configuration, etc.'),
                Forms\Components\TextInput::make('task_name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('e.g., Online Kick Off Meeting, Import User File, etc.'),
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->helperText('Order of task within the module'),
                Forms\Components\TextInput::make('percentage')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->helperText('Completion percentage for this task template'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('module')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'phase_1' => 'info',
                        'phase_2' => 'success',
                        'phase_3' => 'warning',
                        'phase_4' => 'danger',
                        'phase_5' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ProjectTask::getModules()[$state] ?? ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('module_order')
                    ->label('Module Order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('phase_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('task_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Task Order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->suffix('%')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 100 => 'success',
                        $state >= 50 => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('default_duration')
                    ->suffix(' days')
                    ->sortable(),
                Tables\Columns\TextColumn::make('projectPlans_count')
                    ->counts('projectPlans')
                    ->label('Used in Projects')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->options(ProjectTask::getModules()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('module_order')
            ->defaultSort('order', 'asc')
            ->groups([
                Tables\Grouping\Group::make('module')
                    ->label('Module')
                    ->collapsible(),
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
}
