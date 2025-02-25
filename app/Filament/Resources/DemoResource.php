<?php

namespace App\Filament\Resources;

use App\Classes\Encryptor;
use App\Filament\Resources\DemoResource\Pages;
use App\Filament\Resources\DemoResource\RelationManagers;
use App\Models\Appointment;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DemoResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-microphone';

    // public static function canAccess(): bool
    // {
    //     return auth()->user()->role_id != '2';
    // }
    public static function canAccess(): bool
    {
        return false; // Hides the resource from all users
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
            Tables\Columns\TextColumn::make('lead.companyDetail.company_name')
                ->label('COMPANY NAME'),
            Tables\Columns\TextColumn::make('type')
                ->label('TYPE'),
            Tables\Columns\TextColumn::make('appointment_type')
                ->label('APPOINTMENT TYPE'),
            Tables\Columns\TextColumn::make('date')
                ->label('Date')
                ->date(),
            Tables\Columns\TextColumn::make('start_time')
                ->label('Start Time'),
            Tables\Columns\TextColumn::make('end_time')
                ->label('End Time'),

            Tables\Columns\TextColumn::make('status')
                ->label('STATUS')
                ->badge()
                ->color(function ($state) {
                    return match ($state) {
                        'New' => 'warning', // Orange for "New"
                        'Done' => 'success', // Green for "Done"
                        'Cancelled' => 'gray', // Gray for "Cancelled"
                        default => 'secondary', // Default color
                    };
                })
        ])
        ->defaultSort('status', 'asc')
        ->actions([
            Tables\Actions\ViewAction::make()
            ->url(fn ($record) => route('filament.admin.resources.leads.view', [
                'record' => Encryptor::encrypt($record->lead_id),
            ]))
            ->label('') // Remove the label
            ->extraAttributes(['class' => 'hidden']),
        ])
        ->modifyQueryUsing(function (Appointment $appointment) {
            $currentUser = auth('web')->user();

            if ($currentUser->role_id == 3) {
                // If role_id is 3, return all quotations ordered by ID
                return $appointment->orderByRaw("FIELD(status, 'New', 'Done', 'Cancelled')")
                ->orderBy('date', 'asc');
            }else if ($currentUser->role_id == 2) {
                // Fetch the quotations related to the lead where the current user is either the lead owner or the salesperson
                return $appointment->whereHas('lead', function ($query) use ($currentUser) {
                    $query->where('lead_owner', $currentUser->name); // Lead owner (by name)
                })->orWhere('salesperson', $currentUser->id) // Salesperson (by ID)
                ->orderByRaw("FIELD(status, 'New', 'Done', 'Cancelled')")
                ->orderBy('date', 'asc');
            }else{
                return $appointment->whereHas('lead', function ($query) {
                    $query->where('lead_owner', auth()->user()->name);
                })->orderByRaw("FIELD(status, 'New', 'Done', 'Cancelled')")
                ->orderBy('date', 'asc');
            }
        });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDemos::route('/'),
            'view' => LeadResource\Pages\ViewLeadRecord::route('/{record}'),
            // 'create' => Pages\CreateDemo::route('/create'),
            // 'edit' => Pages\EditDemo::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
