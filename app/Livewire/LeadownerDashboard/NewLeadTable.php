<?php

namespace App\Livewire\LeadownerDashboard;

use App\Classes\Encryptor;
use App\Filament\Actions\LeadActions;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class NewLeadTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser; // Allow dynamic filtering
    public $lastRefreshTime;
    public $hasDuplicatesInBulkAssign = false;
    public $duplicateLeadIds = [];

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

    #[On('refresh-leadowner-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function getPendingLeadsQuery()
    {
        $query = Lead::query()
            ->where('lead_code', '!=', 'Apollo')
            ->where('categories', 'New')
            ->whereNull('salesperson') // Still keeping this condition unless you want to include assigned ones too
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_days');

        if ($this->selectedUser === 'all-lead-owners') {
            $leadOwnerNames = User::where('role_id', 1)->pluck('name');
            $query->whereIn('lead_owner', $leadOwnerNames);
        } elseif ($this->selectedUser === 'all-salespersons') {
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereIn('salesperson', $salespersonIds);
        } elseif ($this->selectedUser) {
            $selectedUser = User::find($this->selectedUser);

            if ($selectedUser) {
                if ($selectedUser->role_id == 1) {
                    $query->where('lead_owner', $selectedUser->name);
                } elseif ($selectedUser->role_id == 2) {
                    $query->where('salesperson', $selectedUser->id);
                }
            }
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->query($this->getPendingLeadsQuery())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->filters([
                SelectFilter::make('company_size_label') // Use the correct filter key
                    ->label('')
                    ->options([
                        'Small' => 'Small',
                        'Medium' => 'Medium',
                        'Large' => 'Large',
                        'Enterprise' => 'Enterprise',
                    ])
                    ->multiple() // Enables multi-selection
                    ->placeholder('Select Company Size')
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['values'])) { // 'values' stores multiple selections
                            $sizeMap = [
                                'Small' => '1-24',
                                'Medium' => '25-99',
                                'Large' => '100-500',
                                'Enterprise' => '501 and Above',
                            ];

                            // Convert selected sizes to DB values
                            $dbValues = collect($data['values'])->map(fn ($size) => $sizeMap[$size] ?? null)->filter();

                            if ($dbValues->isNotEmpty()) {
                                $query->whereHas('companyDetail', function ($query) use ($dbValues) {
                                    $query->whereIn('company_size', $dbValues);
                                });
                            }
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return !empty($data['values'])
                            ? 'Company Size: ' . implode(', ', $data['values'])
                            : null;
                    }),
                SelectFilter::make('lead_owner')
                    ->label('')
                    ->multiple()
                    ->options(\App\Models\User::where('role_id', 1)->pluck('name', 'name')->toArray())
                    ->placeholder('Select Lead Owner')
                    ->hidden(fn () => auth()->user()->role_id !== 3),
            ])
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('lead_code')
                    ->label('Lead Source')
                    ->sortable(),

                TextColumn::make('company_size_label')
                    ->label('Company Size')
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("
                            CASE
                                WHEN company_size = '1-24' THEN 1
                                WHEN company_size = '25-99' THEN 2
                                WHEN company_size = '100-500' THEN 3
                                WHEN company_size = '501 and Above' THEN 4
                                ELSE 5
                            END $direction
                        ");
                    }),
                TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->pending_days . ' days')
                    ->color(fn ($record) => $record->pending_days == 0 ? 'draft' : 'danger'),
            ])
            ->actions([
                ActionGroup::make([
                    LeadActions::getViewAction(),
                    LeadActions::getAssignToMeAction(),
                    LeadActions::getAssignLeadAction(),
                    LeadActions::getViewReferralDetailsAction(),
                ])
                ->button()
                ->color(fn (Lead $record) => $record->follow_up_needed ? 'warning' : 'danger'),
            ])
            ->bulkActions([
                BulkAction::make('Assign to Me')
                    ->label('Assign Selected Leads to Me')
                    ->requiresConfirmation()
                    ->modalHeading('Bulk Assign Leads')
                    ->form(function ($records) {
                        $duplicateInfo = [];
                        $hasDuplicates = false;
                        $duplicateLeadIds = [];

                        $allCompanyNames = Lead::query()
                            ->with('companyDetail')
                            ->whereHas('companyDetail')
                            ->get()
                            ->pluck('companyDetail.company_name', 'id')
                            ->filter();

                        foreach ($records as $record) {
                            $companyName = optional($record?->companyDetail)->company_name;

                            $normalizedCompanyName = null;
                            if ($companyName) {
                                $normalizedCompanyName = strtoupper($companyName);
                                $normalizedCompanyName = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedCompanyName);
                                $normalizedCompanyName = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedCompanyName);
                                $normalizedCompanyName = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedCompanyName);
                                $normalizedCompanyName = preg_replace('/\s+/', ' ', $normalizedCompanyName);
                                $normalizedCompanyName = trim($normalizedCompanyName);
                            }

                            $fuzzyMatches = [];
                            if ($normalizedCompanyName) {
                                foreach ($allCompanyNames as $leadId => $existingCompanyName) {
                                    if ($leadId == $record->id) continue;

                                    $normalizedExisting = strtoupper($existingCompanyName);
                                    $normalizedExisting = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedExisting);
                                    $normalizedExisting = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedExisting);
                                    $normalizedExisting = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedExisting);
                                    $normalizedExisting = preg_replace('/\s+/', ' ', $normalizedExisting);
                                    $normalizedExisting = trim($normalizedExisting);

                                    $distance = levenshtein($normalizedCompanyName, $normalizedExisting);
                                    if ($distance > 0 && $distance < 3) {
                                        $fuzzyMatches[] = $existingCompanyName;
                                    }
                                }
                            }

                            $duplicateLeads = Lead::query()
                                ->with('companyDetail')
                                ->where(function ($query) use ($record, $normalizedCompanyName, $fuzzyMatches) {
                                    if ($normalizedCompanyName) {
                                        $query->whereHas('companyDetail', function ($q) use ($normalizedCompanyName) {
                                            $q->whereRaw("UPPER(TRIM(company_name)) LIKE ?", ['%' . $normalizedCompanyName . '%']);
                                        });
                                    }

                                    if (!empty($fuzzyMatches)) {
                                        $query->orWhereHas('companyDetail', function ($q) use ($fuzzyMatches) {
                                            $q->whereIn('company_name', $fuzzyMatches);
                                        });
                                    }

                                    if (!empty($record?->email)) {
                                        $query->orWhere('email', $record->email)
                                            ->orWhereHas('companyDetail', function ($q) use ($record) {
                                                $q->where('email', $record->email);
                                            });
                                    }

                                    if (!empty($record?->companyDetail?->email)) {
                                        $query->orWhere('email', $record->companyDetail->email)
                                            ->orWhereHas('companyDetail', function ($q) use ($record) {
                                                $q->where('email', $record->companyDetail->email);
                                            });
                                    }

                                    if (!empty($record?->phone)) {
                                        $query->orWhere('phone', $record->phone)
                                            ->orWhereHas('companyDetail', function ($q) use ($record) {
                                                $q->where('contact_no', $record->phone);
                                            });
                                    }

                                    if (!empty($record?->companyDetail?->contact_no)) {
                                        $query->orWhere('phone', $record->companyDetail->contact_no)
                                            ->orWhereHas('companyDetail', function ($q) use ($record) {
                                                $q->where('contact_no', $record->companyDetail->contact_no);
                                            });
                                    }
                                })
                                ->where('id', '!=', optional($record)->id)
                                ->get();

                            if ($duplicateLeads->isNotEmpty()) {
                                $hasDuplicates = true;
                                $duplicateLeadIds[] = $record->id;

                                $duplicateDetails = $duplicateLeads->map(function ($lead) {
                                    $dupCompanyName = $lead->companyDetail->company_name ?? 'Unknown Company';
                                    $leadId = str_pad($lead->id, 5, '0', STR_PAD_LEFT);
                                    return "<strong>{$dupCompanyName}</strong> (LEAD ID {$leadId})";
                                })->implode(", ");

                                $duplicateInfo[] = "⚠️ <strong>" . ($companyName ?? 'Lead ' . $record->id) . "</strong> matches: " . $duplicateDetails;
                            }
                        }

                        $this->hasDuplicatesInBulkAssign = $hasDuplicates;
                        $this->duplicateLeadIds = $duplicateLeadIds;

                        $warningMessage = $hasDuplicates
                            ? "⚠️⚠️⚠️ <strong style='color: red;'>Warning: Some leads have duplicates!</strong><br><br>"
                            . implode("<br><br>", $duplicateInfo)
                            . "<br><br><strong style='color: red;'>You can request bypass approval from the manager by clicking 'Request Bypass' button below.</strong>"
                            : "You are about to assign <strong>" . count($records) . "</strong> lead(s) to yourself. Make sure to confirm assignment before contacting the leads to avoid duplicate efforts by other team members.";

                        return [
                            Placeholder::make('warning')
                                ->content(new \Illuminate\Support\HtmlString($warningMessage))
                                ->hiddenLabel()
                                ->extraAttributes([
                                    'style' => $hasDuplicates ? 'color: red; font-weight: bold;' : '',
                                ]),
                        ];
                    })
                    ->action(function ($records) {
                        if ($this->hasDuplicatesInBulkAssign) {
                            Notification::make()
                                ->title('Assignment Blocked')
                                ->body('Duplicate leads detected. Please request bypass approval or cancel.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $this->bulkAssignToMe($records);
                    })
                    ->color('primary')
                    ->modalWidth('xl')
                    ->modalSubmitAction(fn ($action) =>
                        $this->hasDuplicatesInBulkAssign ? $action->hidden() : $action
                    )
                    ->extraModalFooterActions(function ($records) {
                        // ✅ Calculate duplicate lead IDs here again for the action
                        $duplicateLeadIds = [];

                        if ($this->hasDuplicatesInBulkAssign) {
                            foreach ($records as $record) {
                                $companyName = optional($record?->companyDetail)->company_name;

                                $normalizedCompanyName = null;
                                if ($companyName) {
                                    $normalizedCompanyName = strtoupper($companyName);
                                    $normalizedCompanyName = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedCompanyName);
                                    $normalizedCompanyName = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedCompanyName);
                                    $normalizedCompanyName = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedCompanyName);
                                    $normalizedCompanyName = preg_replace('/\s+/', ' ', $normalizedCompanyName);
                                    $normalizedCompanyName = trim($normalizedCompanyName);
                                }

                                $duplicateLeads = Lead::query()
                                    ->with('companyDetail')
                                    ->where(function ($query) use ($record, $normalizedCompanyName) {
                                        if ($normalizedCompanyName) {
                                            $query->whereHas('companyDetail', function ($q) use ($normalizedCompanyName) {
                                                $q->whereRaw("UPPER(TRIM(company_name)) LIKE ?", ['%' . $normalizedCompanyName . '%']);
                                            });
                                        }

                                        if (!empty($record?->email)) {
                                            $query->orWhere('email', $record->email)
                                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                                    $q->where('email', $record->email);
                                                });
                                        }

                                        if (!empty($record?->companyDetail?->email)) {
                                            $query->orWhere('email', $record->companyDetail->email)
                                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                                    $q->where('email', $record->companyDetail->email);
                                                });
                                        }

                                        if (!empty($record?->phone)) {
                                            $query->orWhere('phone', $record->phone)
                                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                                    $q->where('contact_no', $record->phone);
                                                });
                                        }

                                        if (!empty($record?->companyDetail?->contact_no)) {
                                            $query->orWhere('phone', $record->companyDetail->contact_no)
                                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                                    $q->where('contact_no', $record->companyDetail->contact_no);
                                                });
                                        }
                                    })
                                    ->where('id', '!=', optional($record)->id)
                                    ->exists();

                                if ($duplicateLeads) {
                                    $duplicateLeadIds[] = $record->id;
                                }
                            }
                        }

                        return [
                            \Filament\Tables\Actions\Action::make('request_bypass')
                                ->label('Request Bypass')
                                ->icon('heroicon-o-shield-exclamation')
                                ->color('warning')
                                ->visible(fn () => $this->hasDuplicatesInBulkAssign)
                                ->requiresConfirmation()
                                ->modalHeading('Request Bypass Approval')
                                ->modalDescription('Submit a request to bypass duplicate lead checking. A manager must approve this request before you can proceed.')
                                ->form([
                                    \Filament\Forms\Components\Textarea::make('reason')
                                        ->label('Reason for Bypass Request')
                                        ->placeholder('Explain why you need to bypass duplicate checking...')
                                        ->required()
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ])
                                ->action(function (array $data) use ($records, $duplicateLeadIds) {
                                    $user = auth()->user();
                                    $createdCount = 0;

                                    foreach ($records as $record) {
                                        // Only create request for leads with duplicates
                                        if (!in_array($record->id, $duplicateLeadIds)) {
                                            continue;
                                        }

                                        // Check if there's already a pending request
                                        $existingRequest = \App\Models\Request::where('lead_id', $record->id)
                                            ->where('request_type', 'bypass_duplicate')
                                            ->where('status', 'pending')
                                            ->first();

                                        if ($existingRequest) {
                                            continue;
                                        }

                                        // Get duplicate info
                                        $companyName = optional($record?->companyDetail)->company_name;
                                        $duplicateInfo = [
                                            'company_name' => $companyName,
                                            'lead_id' => $record->id,
                                            'email' => $record->email ?? $record->companyDetail?->email,
                                            'phone' => $record->phone ?? $record->companyDetail?->contact_no,
                                        ];

                                        \App\Models\Request::create([
                                            'lead_id' => $record->id,
                                            'requested_by' => $user->id,
                                            'request_type' => 'bypass_duplicate',
                                            'duplicate_info' => $duplicateInfo,
                                            'reason' => $data['reason'],
                                            'status' => 'pending',
                                        ]);

                                        $createdCount++;
                                    }

                                    if ($createdCount > 0) {
                                        // Notify managers
                                        $managers = User::where('role_id', 3)->get();
                                        foreach ($managers as $manager) {
                                            Notification::make()
                                                ->title('Bypass Request Pending Approval')
                                                ->body("{$user->name} requested to bypass duplicate checking for {$createdCount} lead(s). Reason: {$data['reason']}")
                                                ->icon('heroicon-o-shield-exclamation')
                                                ->warning()
                                                ->sendToDatabase($manager);
                                        }

                                        Notification::make()
                                            ->title('Bypass Request Submitted')
                                            ->body("Your request for {$createdCount} lead(s) has been submitted and is pending manager approval.")
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('No New Requests Created')
                                            ->body('All selected leads either have no duplicates or already have pending requests.')
                                            ->warning()
                                            ->send();
                                    }
                                })
                                ->cancelParentActions(),
                        ];
                    }),
            ]);
    }

    public function bulkAssignToMe($records)
    {
        $user = auth()->user();

        foreach ($records as $record) {
            // Update the lead owner and related fields
            $record->update([
                'lead_owner' => $user->name,
                'categories' => 'Active',
                'stage' => 'Transfer',
                'lead_status' => 'New',
                'pickup_date' => now(),
            ]);

            // Update the latest activity log
            $latestActivityLog = ActivityLog::where('subject_id', $record->id)
                ->orderByDesc('created_at')
                ->first();

            if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Lead Owner: ' . $user->name) {
                $latestActivityLog->update([
                    'description' => 'Lead assigned to Lead Owner: ' . $user->name,
                ]);

                activity()
                    ->causedBy($user)
                    ->performedOn($record);
            }
        }

        Notification::make()
            ->title(count($records) . ' Leads Assigned Successfully')
            ->success()
            ->send();
    }

    public function render()
    {
        return view('livewire.leadowner_dashboard.new-lead-table');
    }
}
