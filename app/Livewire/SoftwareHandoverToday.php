<?php

namespace App\Livewire;

use App\Models\SoftwareHandover;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\View\View;

class SoftwareHandoverToday extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getNewSoftwareHandovers()
    {
        return SoftwareHandover::query()
            ->where('status', 'New')
            ->whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->query($this->getNewSoftwareHandovers())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            // ->filters([
            //     // Filter for Creator
            //     SelectFilter::make('created_by')
            //         ->label('Created By')
            //         ->multiple()
            //         ->options(User::pluck('name', 'id')->toArray())
            //         ->placeholder('Select User'),

            //     // Filter by Company Name
            //     SelectFilter::make('company_name')
            //         ->label('Company')
            //         ->searchable()
            //         ->options(SoftwareHandover::distinct()->pluck('company_name', 'company_name')->toArray())
            //         ->placeholder('Select Company'),
            // ])
            ->columns([
                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name'),

                TextColumn::make('handover_pdf')
                    ->label('ID')
                    ->formatStateUsing(function ($state) {
                        // If handover_pdf is null, return a placeholder
                        if (!$state) {
                            return '-';
                        }

                        // Extract just the filename without extension
                        $filename = basename($state, '.pdf');

                        // Return just the formatted ID part
                        return $filename;
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(' ')
                            ->modalWidth('md')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('value')
                    ->label('Value'),
            ])
            ->actions([
                ActionGroup::make([
                    // Action::make('view_details')
                    //     ->label('View Details')
                    //     ->icon('heroicon-o-eye')
                    //     ->url(fn (SoftwareHandover $record): string => route('filament.admin.resources.software-handovers.view', $record)),

                    Action::make('mark_approved')
                        ->label('Approved')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (SoftwareHandover $record): void {
                            $record->update(['status' => 'Approved']);

                            Notification::make()
                                ->title('Software Handover marked as approved')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->hidden(fn (SoftwareHandover $record): bool => $record->status !== 'New'),

                    Action::make('mark_rejected')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            \Filament\Forms\Components\Textarea::make('reject_reason')
                                ->label('Reason for Rejection')
                                ->required()
                                ->placeholder('Please provide a reason for rejecting this handover')
                                ->maxLength(500)
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // Update both status and add the rejection remarks
                            $record->update([
                                'status' => 'Rejected',
                                'remark' => $data['reject_reason']
                            ]);

                            Notification::make()
                                ->title('Hardware Handover marked as rejected')
                                ->body('Rejection reason: ' . $data['reject_reason'])
                                ->danger()
                                ->send();
                        })
                        ->requiresConfirmation(false),
                ])
                ->button()
                ->label('Actions')
            ]);
    }

    public function render()
    {
        return view('livewire.software-handover-today');
    }
}
