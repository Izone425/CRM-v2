<?php

namespace App\Livewire;

use App\Models\Lead;
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
use Illuminate\Support\HtmlString;
use Illuminate\View\View;

class SoftwareHandoverOverdue extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getNewSoftwareHandovers()
    {
        $query = SoftwareHandover::query();

        if (auth()->user()->role_id === 2) {
            // Salespersons (role_id 2) can see Draft, New, Approved, and Completed
            $query->whereIn('status', ['Completed']);

            // But only THEIR OWN records
            $userId = auth()->id();
            $query->whereHas('lead', function ($leadQuery) use ($userId) {
                $leadQuery->where('salesperson', $userId);
            });
        } else {
            // Other users (admin, managers) can only see New, Approved, and Completed
            $query->whereIn('status', ['Completed']);
            // But they can see ALL records
        }

        $query->orderByRaw("CASE
            WHEN status = 'New' THEN 1
            WHEN status = 'Approved' THEN 2
            WHEN status = 'Completed' THEN 3
            ELSE 4
        END")
        ->orderBy('created_at', 'desc');

        return $query;
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

                TextColumn::make('lead.salesperson')
                    ->label('SALESPERSON')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 20, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),

                // TextColumn::make('submitted_at')
                //     ->label('Date Submit')
                //     ->date('d M Y'),

                // TextColumn::make('kik_off_meeting_date')
                //     ->label('Kick Off Meeting Date')
                //     ->formatStateUsing(function ($state) {
                //         return $state ? Carbon::parse($state)->format('d M Y') : 'N/A';
                //     })
                //     ->date('d M Y'),

                // TextColumn::make('training_date')
                //     ->label('Training Date')
                //     ->formatStateUsing(function ($state) {
                //         return $state ? Carbon::parse($state)->format('d M Y') : 'N/A';
                //     })
                //     ->date('d M Y'),

                // TextColumn::make('training_date')
                //     ->label('Implementer')
                //     ->formatStateUsing(function ($state) {
                //         return $state ? Carbon::parse($state)->format('d M Y') : 'N/A';
                //     })
                //     ->date('d M Y'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(' ')
                        ->modalWidth('md')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->visible(fn (SoftwareHandover $record): bool => in_array($record->status, ['New', 'Completed', 'Approved']))
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),
                    Action::make('mark_approved')
                        ->label('Approve')
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
                        ->hidden(fn (SoftwareHandover $record): bool =>
                            $record->status !== 'New' || auth()->user()->role_id === 2
                        ),
                    Action::make('mark_rejected')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->hidden(fn (SoftwareHandover $record): bool =>
                            $record->status !== 'New' || auth()->user()->role_id === 2
                        )
                        ->form([
                            \Filament\Forms\Components\Textarea::make('reject_reason')
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->label('Reason for Rejection')
                                ->required()
                                ->placeholder('Please provide a reason for rejecting this handover')
                                ->maxLength(500)
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // Update both status and add the rejection remarks
                            $record->update([
                                'status' => 'Rejected',
                                'reject_reason' => $data['reject_reason']
                            ]);

                            Notification::make()
                                ->title('Hardware Handover marked as rejected')
                                ->body('Rejection reason: ' . $data['reject_reason'])
                                ->danger()
                                ->send();
                        })
                        ->requiresConfirmation(false),
                    Action::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-badge') // Using check badge icon to distinguish from regular approval
                        ->color('success') // Using success color for completion
                        ->action(function (SoftwareHandover $record): void {
                            $record->update(['status' => 'Completed']);

                            Notification::make()
                                ->title('Software Handover marked as completed')
                                ->body('This handover has been marked as completed.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->hidden(fn (SoftwareHandover $record): bool =>
                            $record->status !== 'Approved' || auth()->user()->role_id === 2
                        ),
                ])
            ]);
    }

    public function render()
    {
        return view('livewire.software-handover-overdue');
    }
}
