<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadSourceResource\Pages;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use Filament\Forms\Components\Section;
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

            Section::make('Access Permissions')
                ->description('Control which user roles can select this lead source')
                ->schema([
                    Toggle::make('accessible_by_lead_owners')
                        ->label('Lead Owners')
                        ->helperText('Can Lead Owners select this lead source?')
                        ->default(true),

                    Toggle::make('accessible_by_timetec_hr_salespeople')
                        ->label('TimeTec HR Salespeople')
                        ->helperText('Can TimeTec HR Salespeople select this lead source?')
                        ->default(true),

                    Toggle::make('accessible_by_non_timetec_hr_salespeople')
                        ->label('Non-TimeTec HR Salespeople')
                        ->helperText('Can Non-TimeTec HR Salespeople select this lead source?')
                        ->default(true),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lead_code')->sortable()->searchable(),

                ToggleColumn::make('accessible_by_lead_owners')
                    ->label('Lead Owners')
                    ->disabled()
                    ->sortable(),

                ToggleColumn::make('accessible_by_timetec_hr_salespeople')
                    ->label('HR Sales')
                    ->disabled()
                    ->sortable(),

                ToggleColumn::make('accessible_by_non_timetec_hr_salespeople')
                    ->label('Non-HR Sales')
                    ->disabled()
                    ->sortable(),
            ])
            ->actions([
                EditAction::make()
                    ->closeModalByClickingAway(false),
                    // ->hidden(function (LeadSource $record): bool {
                    //     return Lead::where('lead_code', $record->lead_code)->exists();
                    // }),
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
