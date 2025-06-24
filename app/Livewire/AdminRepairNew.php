<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateRepairPdfController;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\AdminRepair;
use App\Models\User;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action as FormAction;
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
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class AdminRepairNew extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexDeviceCounter = 0;
    protected static ?int $indexRemarkCounter = 0;

    public $selectedUser;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-adminrepair-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]);

        $this->resetTable();
    }

    public function getTableQuery(): Builder
    {
        $query = AdminRepair::query()
            ->where('status', 'New')
            ->orderBy('created_at', 'desc');

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getTableQuery())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Draft' => 'Draft',
                        'New' => 'New',
                        'In Progress' => 'In Progress',
                        'Awaiting Parts' => 'Awaiting Parts',
                        'Resolved' => 'Resolved',
                        'Closed' => 'Closed',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),

                Filter::make('created_at')
                    ->form([
                        DateRangePicker::make('date_range')
                            ->label('')
                            ->placeholder('Select date range'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);
                            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
                            $query->whereBetween('created_at', [$startDate, $endDate]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (!empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);
                            return 'From: ' . Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                                ' To: ' . Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                        }
                        return null;
                    }),

                SortFilter::make("sort_by"),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return 'RP_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('id', $direction);
                    })
                    ->action(
                        Action::make('viewRepairDetails')
                            ->modalHeading(' ')
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (AdminRepair $record): View {
                                return view('components.repair-detail')
                                    ->with('record', $record);
                            })
                    ),

                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pic_name')
                    ->label('PIC Name')
                    ->searchable(),

                TextColumn::make('devices')
                    ->label('Devices')
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        if ($record->devices) {
                            $devices = is_string($record->devices)
                                ? json_decode($record->devices, true)
                                : $record->devices;

                            if (is_array($devices)) {
                                return collect($devices)
                                    ->map(fn ($device) =>
                                        "{$device['device_model']} (SN: {$device['device_serial']})")
                                    ->join('<br>');
                            }
                        }

                        if ($record->device_model) {
                            return "{$record->device_model} (SN: {$record->device_serial})";
                        }

                        return '—';
                    })
                    ->html()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('device_model', 'like', "%{$search}%")
                            ->orWhere('device_serial', 'like', "%{$search}%")
                            ->orWhere('devices', 'like', "%{$search}%");
                    }),

                TextColumn::make('zoho_ticket')
                    ->label('Zoho Ticket')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'New' => 'danger',
                        'In Progress' => 'warning',
                        'Awaiting Parts' => 'info',
                        'Resolved' => 'success',
                        'Closed' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    // View detail action
                    Action::make('view')
                        ->icon('heroicon-o-eye')
                        ->modalHeading(fn (AdminRepair $record) => "Repair Handover Form " . 'RP_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT))
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (AdminRepair $record): View {
                            return view('components.repair-detail')
                                ->with('record', $record);
                        }),

                    // Action::make('accept_repair')
                    //     ->label('Accept Repair')
                    //     ->modalWidth('3xl')
                    //     ->icon('heroicon-o-check-circle')
                    //     ->color('success')
                    //     ->visible(fn (AdminRepair $record): bool => $record->status === 'New' && auth()->user()->role_id !== 1)
                    //     ->form([
                    //         Section::make('Repair Acceptance')
                    //             ->schema([
                    //                 Repeater::make('repair_remarks')
                    //                 ->hiddenLabel()
                    //                 ->schema([
                    //                     Grid::make(2)
                    //                     ->schema([
                    //                         Textarea::make('repair_remark')
                    //                             ->hiddenLabel()
                    //                             ->required()
                    //                             ->placeholder('Enter repair assessment notes')
                    //                             ->rows(3),

                    //                         FileUpload::make('attachments')
                    //                             ->hiddenLabel()
                    //                             ->disk('public')
                    //                             ->directory('repair-attachments/assessments')
                    //                             ->visibility('public')
                    //                             ->multiple()
                    //                             ->maxFiles(5)
                    //                             ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                    //                             ->openable()
                    //                             ->downloadable()
                    //                             ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, AdminRepair $record): string {
                    //                                 $repairId = 'RP_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    //                                 $extension = $file->getClientOriginalExtension();
                    //                                 $timestamp = now()->format('YmdHis');
                    //                                 $random = rand(1000, 9999);

                    //                                 return "{$repairId}-assessment-{$timestamp}-{$random}.{$extension}";
                    //                             }),
                    //                     ]),
                    //                 ])
                    //                 ->defaultItems(1)
                    //                 ->minItems(1)
                    //                 ->maxItems(5)
                    //                 ->itemLabel(function (array $state): ?string {
                    //                     return $state['repair_remark'] ?? 'New Repair Remark';
                    //                 })
                    //                 ->addActionLabel('Add Another Remark')
                    //                 ->reorderableWithDragAndDrop(false)
                    //                 ->grid(1),
                    //             ]),

                    //         Section::make('Device Details')
                    //             ->schema([
                    //                 Select::make('model_id')
                    //                     ->label('Device Model')
                    //                     ->options([
                    //                         'TC10' => 'TC10',
                    //                         'TC20' => 'TC20',
                    //                         'FACE ID 5' => 'FACE ID 5',
                    //                         'FACE ID 6' => 'FACE ID 6',
                    //                         'TIME BEACON' => 'TIME BEACON',
                    //                         'NFC TAG' => 'NFC TAG',
                    //                     ])
                    //                     ->searchable()
                    //                     ->required()
                    //                     ->reactive() // This makes the field reactive
                    //                     ->afterStateUpdated(function ($state, callable $set) {
                    //                         // Clear spare parts selection when model changes
                    //                         $set('spare_parts', []);
                    //                     })
                    //                     ->placeholder('Select device model'),

                    //                 Select::make('spare_parts')
                    //                     ->label('Spare Parts Required')
                    //                     ->allowHtml()
                    //                     ->searchable()
                    //                     ->multiple()
                    //                     ->preload()
                    //                     ->optionsLimit(50)
                    //                     ->placeholder('Select required spare parts')
                    //                     ->helperText('Select all spare parts that may be needed for this repair')
                    //                     ->loadingMessage('Loading spare parts...')
                    //                     ->noSearchResultsMessage('No spare parts found')
                    //                     ->options(function (callable $get) {
                    //                         // Get the selected device model
                    //                         $selectedModel = $get('model_id');

                    //                         // If no model is selected, return empty array
                    //                         if (!$selectedModel) {
                    //                             return [];
                    //                         }

                    //                         // Get spare parts matching the selected model
                    //                         $spareParts = \App\Models\SparePart::where('is_active', true)
                    //                             ->where('device_model', $selectedModel)
                    //                             ->limit(50)
                    //                             ->get();

                    //                         // Format the options
                    //                         return $spareParts->mapWithKeys(function ($part) {
                    //                             return [$part->id => static::getSparePartOptionHtml($part)];
                    //                         })->toArray();
                    //                     })
                    //                     ->getSearchResultsUsing(function (string $search, callable $get) {
                    //                         // Get the selected device model
                    //                         $selectedModel = $get('model_id');

                    //                         // If no model is selected, return empty array
                    //                         if (!$selectedModel) {
                    //                             return [];
                    //                         }

                    //                         // Find spare parts that match the search and the selected model
                    //                         $spareParts = \App\Models\SparePart::where('is_active', true)
                    //                             ->where('device_model', $selectedModel)
                    //                             ->where(function ($query) use ($search) {
                    //                                 $query->where('name', 'like', "%{$search}%");
                    //                             })
                    //                             ->limit(50)
                    //                             ->get();

                    //                         // Format the results
                    //                         return $spareParts->mapWithKeys(function ($part) {
                    //                             return [$part->id => static::getSparePartOptionHtml($part)];
                    //                         })->toArray();
                    //                     })
                    //                     ->getOptionLabelUsing(function ($value) {
                    //                         // Get clean label for selected value
                    //                         $part = \App\Models\SparePart::find($value);
                    //                         return $part ? $part->name : null;
                    //                     })
                    //                     ->disabled(fn (callable $get) => !$get('model_id'))
                    //             ]),
                    //     ])
                    //     ->action(function (AdminRepair $record, array $data): void {
                    //         // Update repair record with status and spare parts
                    //         $record->update([
                    //             'status' => 'Accepted',
                    //             'spare_parts' => !empty($data['spare_parts']) ? json_encode($data['spare_parts']) : null,
                    //             'updated_by' => auth()->id(),
                    //         ]);

                    //         // Process repair remarks from the repeater
                    //         $currentRemarks = [];

                    //         // If there are existing repair remarks, retrieve them
                    //         if ($record->repair_remark) {
                    //             $currentRemarks = is_string($record->repair_remark)
                    //                 ? json_decode($record->repair_remark, true)
                    //                 : $record->repair_remark;

                    //             if (!is_array($currentRemarks)) {
                    //                 $currentRemarks = [];
                    //             }
                    //         }

                    //         // Process each remark from the repeater
                    //         if (!empty($data['repair_remarks'])) {
                    //             foreach ($data['repair_remarks'] as $remarkData) {
                    //                 // Add new remark with acceptance details
                    //                 $currentRemarks[] = [
                    //                     'remark' => $remarkData['repair_remark'],
                    //                     'attachments' => !empty($remarkData['attachments']) ? json_encode($remarkData['attachments']) : json_encode([]),
                    //                 ];
                    //             }
                    //         }

                    //         // Update the record with new repair remarks
                    //         $record->update([
                    //             'repair_remark' => json_encode($currentRemarks),
                    //         ]);

                    //         // Send email notification
                    //         try {
                    //             // Format repair ID
                    //             $repairId = 'RP_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

                    //             // Get company name
                    //             $companyName = $record->companyDetail->company_name ?? 'Unknown Company';

                    //             // Get technician name
                    //             $technicianName = auth()->user()->name ?? 'Unknown Technician';

                    //             // Collect all remarks for the email
                    //             $allRemarks = [];
                    //             if (!empty($data['repair_remarks'])) {
                    //                 foreach ($data['repair_remarks'] as $index => $remarkData) {
                    //                     $remarkText = $remarkData['repair_remark'] ?? 'No details provided';
                    //                     $remarkNumber = $index + 1;
                    //                     $allRemarks[] = "Remark #{$remarkNumber}: {$remarkText}";
                    //                 }
                    //             }

                    //             // Format remarks as a string for the email
                    //             $formattedRemarks = !empty($allRemarks)
                    //                 ? implode("\n\n", $allRemarks)
                    //                 : 'Repair accepted';

                    //             // Prepare email data
                    //             $emailData = [
                    //                 'repair_id' => $repairId,
                    //                 'company_name' => $companyName,
                    //                 'technician' => $technicianName,
                    //                 'status' => 'Accepted',
                    //                 'remarks' => $formattedRemarks,
                    //                 'remarks_array' => $allRemarks,
                    //                 'repair_remarks' => $currentRemarks, // Add the full structured remarks
                    //                 'accepted_at' => now()->format('d M Y, h:i A'),
                    //                 'model_id' => $data['model_id'] ?? 'Not specified',
                    //                 'spare_parts' => [], // Will populate below
                    //             ];

                    //             // Add spare parts information if available
                    //             if (!empty($data['spare_parts'])) {
                    //                 $spareParts = [];
                    //                 foreach ($data['spare_parts'] as $partId) {
                    //                     $part = \App\Models\SparePart::find($partId);
                    //                     if ($part) {
                    //                         $imageUrl = null;

                    //                         // Get image URL if available
                    //                         if ($part->picture_url) {
                    //                             $imageUrl = $part->picture_url;

                    //                             // Make sure it's a full URL
                    //                             if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    //                                 if (str_starts_with($imageUrl, 'storage/')) {
                    //                                     $imageUrl = url($imageUrl);
                    //                                 } else {
                    //                                     $imageUrl = url('storage/' . $imageUrl);
                    //                                 }
                    //                             }
                    //                         }

                    //                         $spareParts[] = [
                    //                             'name' => $part->name,
                    //                             'model' => $part->device_model,
                    //                             'image_url' => $imageUrl,
                    //                         ];
                    //                     }
                    //                 }
                    //                 $emailData['spare_parts'] = $spareParts;
                    //             }

                    //             // Recipients
                    //             $recipients = [
                    //                 // 'admin.timetec.hr@timeteccloud.com',
                    //                 // 'izzuddin@timeteccloud.com',
                    //                 'zilih020906@gmail.com',
                    //             ];

                    //             // Send the email
                    //             \Illuminate\Support\Facades\Mail::send(
                    //                 'emails.repair_status_changed',
                    //                 $emailData,
                    //                 function ($message) use ($recipients, $repairId, $companyName) {
                    //                     $message->from(auth()->user()->email, auth()->user()->name);
                    //                     $message->to($recipients);
                    //                     $message->subject("REPAIR TICKET {$repairId} ACCEPTED | {$companyName}");
                    //                 }
                    //             );
                    //         } catch (\Exception $e) {
                    //             // Log error but don't stop the process
                    //             \Illuminate\Support\Facades\Log::error("Failed to send repair acceptance email: " . $e->getMessage());
                    //         }

                    //         // Show success notification
                    //         Notification::make()
                    //             ->title('Repair ticket accepted')
                    //             ->success()
                    //             ->send();
                    //     })
                    //     ->modalHeading('Accept Repair Ticket')
                    //     ->modalSubmitActionLabel('Accept Repair'),
                ])->button()
            ]);
    }

    protected static function getSparePartOptionHtml(\App\Models\SparePart $part): string
    {
        $imageUrl = $part->picture_url ?? url('images/no-image.jpg');
        $fullImageUrl = $imageUrl; // Keep the original URL for the full view

        return '
            <div class="flex items-center w-full gap-2">
                <div class="flex-shrink-0 w-8 h-8">
                    <img src="' . e($imageUrl) . '" class="object-cover w-full h-full rounded"
                        onerror="this.onerror=null; this.src=\'' . e(url('images/no-image.jpg')) . '\'" />
                </div>
                <div class="flex-grow truncate">
                    <div class="font-medium truncate">' . e($part->name) . '</div>
                    <div class="text-xs text-gray-500 truncate">' . e($part->device_model) . '</div>
                </div>
                <div class="flex-shrink-0">
                    <button type="button"
                        onclick="event.stopPropagation(); window.open(\'' . e($fullImageUrl) . '\', \'_blank\'); return false;"
                        class="px-1 py-1 text-xs rounded text-primary-600 hover:text-primary-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>
        ';
    }

    public function render()
    {
        return view('livewire.admin-repair-new');
    }
}
