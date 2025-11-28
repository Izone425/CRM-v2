<?php

namespace App\Livewire\LeadownerDashboard;

use App\Classes\Encryptor;
use App\Filament\Actions\LeadActions;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\Request;
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
                        // ✅ Single duplicate check - cache the result
                        $duplicateInfo = [];
                        $hasDuplicates = false;

                        $allCompanyNames = Lead::query()
                            ->with('companyDetail')
                            ->whereHas('companyDetail')
                            ->get()
                            ->pluck('companyDetail.company_name', 'id')
                            ->filter();

                        foreach ($records as $record) {
                            $companyName = optional($record?->companyDetail)->company_name;

                            // Normalize company name
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
                                ->where(function ($query) use ($record, $normalizedCompanyName) {
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

                                $duplicateDetails = $duplicateLeads->map(function ($lead) {
                                    $dupCompanyName = $lead->companyDetail->company_name ?? 'Unknown Company';
                                    $leadId = str_pad($lead->id, 5, '0', STR_PAD_LEFT);
                                    return "<strong>{$dupCompanyName}</strong> (LEAD ID {$leadId})";
                                })->implode(", ");

                                $duplicateInfo[] = "⚠️ <strong>" . ($companyName ?? 'Lead ' . $record->id) . "</strong> matches: " . $duplicateDetails;
                            }
                        }

                        // ✅ Cache the result in component property
                        $this->hasDuplicatesInBulkAssign = $hasDuplicates;

                        $warningMessage = $hasDuplicates
                            ? "⚠️⚠️⚠️ <strong style='color: red;'>Warning: Some leads have duplicates!</strong><br><br>"
                            . implode("<br><br>", $duplicateInfo)
                            . "<br><br><strong style='color: red;'>Please contact Faiz before proceeding. Assignment is blocked.</strong>"
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
                        // ✅ Use cached result - no re-check needed
                        if ($this->hasDuplicatesInBulkAssign) {
                            Notification::make()
                                ->title('Assignment Blocked')
                                ->body('Duplicate leads detected. Please contact Faiz before proceeding.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $this->bulkAssignToMe($records);
                    })
                    ->color('primary')
                    ->modalWidth('xl')
                    // ✅ Use cached result to hide button
                    ->modalSubmitAction(fn ($action) =>
                        $this->hasDuplicatesInBulkAssign ? $action->hidden() : $action
                    ),
                // BulkAction::make('bulkBypassDuplicate')
                //     ->label('Request Bypass Duplicate')
                //     ->icon('heroicon-o-shield-exclamation')
                //     ->color('warning')
                //     ->requiresConfirmation()
                //     ->modalHeading('Bulk Request Bypass Duplicate Checking')
                //     ->modalDescription(fn ($records) => 'Submit bypass requests for ' . count($records) . ' selected lead(s). Admin approval is required.')
                //     ->form([
                //         \Filament\Forms\Components\Textarea::make('reason')
                //             ->label('Reason for Bypass Requests')
                //             ->placeholder('Explain why these leads should bypass duplicate checking...')
                //             ->required()
                //             ->rows(4)
                //             ->extraAlpineAttributes([
                //                 'x-on:input' => '
                //                     const start = $el.selectionStart;
                //                     const end = $el.selectionEnd;
                //                     const value = $el.value;
                //                     $el.value = value.toUpperCase();
                //                     $el.setSelectionRange(start, end);
                //                 '
                //             ])
                //             ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                //             ->maxLength(500),
                //     ])
                //     ->action(function ($records, array $data) {
                //         $user = auth()->user();
                //         $successCount = 0;

                //         foreach ($records as $record) {
                //             // Check if there's already a pending bypass request
                //             $existingRequest = Request::where('lead_id', $record->id)
                //                 ->where('request_type', 'bypass_duplicate')
                //                 ->where('status', 'pending')
                //                 ->exists();

                //             if ($existingRequest) {
                //                 continue; // Skip if already has pending request
                //             }

                //             // Find and store duplicate information
                //             $companyName = optional($record?->companyDetail)->company_name;
                //             $duplicateInfo = [];

                //             // Normalize company name for duplicate checking
                //             $normalizedCompanyName = null;
                //             if ($companyName) {
                //                 $normalizedCompanyName = strtoupper($companyName);
                //                 $normalizedCompanyName = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedCompanyName);
                //                 $normalizedCompanyName = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedCompanyName);
                //                 $normalizedCompanyName = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedCompanyName);
                //                 $normalizedCompanyName = preg_replace('/\s+/', ' ', $normalizedCompanyName);
                //                 $normalizedCompanyName = trim($normalizedCompanyName);
                //             }

                //             // Get all company names for fuzzy matching
                //             $allCompanyNames = Lead::query()
                //                 ->with('companyDetail')
                //                 ->whereHas('companyDetail')
                //                 ->get()
                //                 ->pluck('companyDetail.company_name', 'id')
                //                 ->filter();

                //             $fuzzyMatches = [];
                //             if ($normalizedCompanyName) {
                //                 foreach ($allCompanyNames as $leadId => $existingCompanyName) {
                //                     if ($leadId == $record->id) continue;

                //                     $normalizedExisting = strtoupper($existingCompanyName);
                //                     $normalizedExisting = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedExisting);
                //                     $normalizedExisting = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedExisting);
                //                     $normalizedExisting = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedExisting);
                //                     $normalizedExisting = preg_replace('/\s+/', ' ', $normalizedExisting);
                //                     $normalizedExisting = trim($normalizedExisting);

                //                     $distance = levenshtein($normalizedCompanyName, $normalizedExisting);
                //                     if ($distance > 0 && $distance < 3) {
                //                         $fuzzyMatches[] = $existingCompanyName;
                //                     }
                //                 }
                //             }

                //             // Find duplicate leads
                //             $duplicateLeads = Lead::query()
                //                 ->with('companyDetail')
                //                 ->where(function ($query) use ($record, $normalizedCompanyName, $fuzzyMatches) {
                //                     if ($normalizedCompanyName) {
                //                         $query->whereHas('companyDetail', function ($q) use ($normalizedCompanyName) {
                //                             $q->whereRaw("UPPER(TRIM(company_name)) LIKE ?", ['%' . $normalizedCompanyName . '%']);
                //                         });
                //                     }

                //                     if (!empty($fuzzyMatches)) {
                //                         $query->orWhereHas('companyDetail', function ($q) use ($fuzzyMatches) {
                //                             $q->whereIn('company_name', $fuzzyMatches);
                //                         });
                //                     }

                //                     if (!empty($record?->email)) {
                //                         $query->orWhere('email', $record->email)
                //                             ->orWhereHas('companyDetail', function ($q) use ($record) {
                //                                 $q->where('email', $record->email);
                //                             });
                //                     }

                //                     if (!empty($record?->companyDetail?->email)) {
                //                         $query->orWhere('email', $record->companyDetail->email)
                //                             ->orWhereHas('companyDetail', function ($q) use ($record) {
                //                                 $q->where('email', $record->companyDetail->email);
                //                             });
                //                     }

                //                     if (!empty($record?->phone)) {
                //                         $query->orWhere('phone', $record->phone)
                //                             ->orWhereHas('companyDetail', function ($q) use ($record) {
                //                                 $q->where('contact_no', $record->phone);
                //                             });
                //                     }

                //                     if (!empty($record?->companyDetail?->contact_no)) {
                //                         $query->orWhere('phone', $record->companyDetail->contact_no)
                //                             ->orWhereHas('companyDetail', function ($q) use ($record) {
                //                                 $q->where('contact_no', $record->companyDetail->contact_no);
                //                             });
                //                     }
                //                 })
                //                 ->where('id', '!=', optional($record)->id)
                //                 ->get();

                //             // Build duplicate info array
                //             if ($duplicateLeads->isNotEmpty()) {
                //                 foreach ($duplicateLeads as $duplicateLead) {
                //                     $duplicateInfo[] = [
                //                         'lead_id' => $duplicateLead->id,
                //                         'company_name' => $duplicateLead->companyDetail->company_name ?? 'Unknown Company',
                //                         'lead_code' => $duplicateLead->lead_code,
                //                         'email' => $duplicateLead->email,
                //                         'phone' => $duplicateLead->phone,
                //                         'lead_owner' => $duplicateLead->lead_owner,
                //                         'categories' => $duplicateLead->categories,
                //                         'created_at' => $duplicateLead->created_at->format('Y-m-d H:i:s'),
                //                         'match_type' => $this->getDuplicateMatchType($record, $duplicateLead, $normalizedCompanyName, $fuzzyMatches),
                //                     ];
                //                 }
                //             }

                //             // Create bypass request with duplicate info
                //             Request::create([
                //                 'lead_id' => $record->id,
                //                 'requested_by' => $user->id,
                //                 'current_owner_id' => null,
                //                 'requested_owner_id' => $user->id,
                //                 'reason' => $data['reason'],
                //                 'status' => 'pending',
                //                 'request_type' => 'bypass_duplicate',
                //                 'duplicate_info' => json_encode([
                //                     'current_lead' => [
                //                         'lead_id' => $record->id,
                //                         'company_name' => $companyName,
                //                         'email' => $record->email,
                //                         'phone' => $record->phone,
                //                         'lead_code' => $record->lead_code,
                //                     ],
                //                     'duplicates_found' => $duplicateInfo,
                //                     'total_duplicates' => count($duplicateInfo),
                //                     'checked_at' => now()->format('Y-m-d H:i:s'),
                //                 ]),
                //             ]);

                //             // Log activity
                //             activity()
                //                 ->causedBy($user)
                //                 ->performedOn($record)
                //                 ->withProperties([
                //                     'reason' => $data['reason'],
                //                     'request_type' => 'bypass_duplicate',
                //                     'duplicates_count' => count($duplicateInfo),
                //                 ])
                //                 ->log('Requested bypass duplicate checking (bulk)');

                //             $successCount++;
                //         }

                //         Notification::make()
                //             ->title('Bypass Requests Submitted')
                //             ->body("{$successCount} bypass request(s) submitted and pending admin approval.")
                //             ->success()
                //             ->send();
                //     })
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

    private function getDuplicateMatchType($currentLead, $duplicateLead, $normalizedCompanyName, $fuzzyMatches)
    {
        $matchTypes = [];

        // Check company name match
        $duplicateCompanyName = optional($duplicateLead->companyDetail)->company_name;
        if ($duplicateCompanyName) {
            $normalizedDuplicateCompany = strtoupper($duplicateCompanyName);
            $normalizedDuplicateCompany = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedDuplicateCompany);
            $normalizedDuplicateCompany = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedDuplicateCompany);
            $normalizedDuplicateCompany = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedDuplicateCompany);
            $normalizedDuplicateCompany = preg_replace('/\s+/', ' ', $normalizedDuplicateCompany);
            $normalizedDuplicateCompany = trim($normalizedDuplicateCompany);

            if ($normalizedCompanyName && strpos($normalizedDuplicateCompany, $normalizedCompanyName) !== false) {
                $matchTypes[] = 'company_name_exact';
            } elseif (in_array($duplicateCompanyName, $fuzzyMatches)) {
                $matchTypes[] = 'company_name_fuzzy';
            }
        }

        // Check email match
        if (!empty($currentLead->email) &&
            ($currentLead->email == $duplicateLead->email ||
            $currentLead->email == optional($duplicateLead->companyDetail)->email)) {
            $matchTypes[] = 'email';
        }

        if (!empty(optional($currentLead->companyDetail)->email) &&
            (optional($currentLead->companyDetail)->email == $duplicateLead->email ||
            optional($currentLead->companyDetail)->email == optional($duplicateLead->companyDetail)->email)) {
            $matchTypes[] = 'company_email';
        }

        // Check phone match
        if (!empty($currentLead->phone) &&
            ($currentLead->phone == $duplicateLead->phone ||
            $currentLead->phone == optional($duplicateLead->companyDetail)->contact_no)) {
            $matchTypes[] = 'phone';
        }

        if (!empty(optional($currentLead->companyDetail)->contact_no) &&
            (optional($currentLead->companyDetail)->contact_no == $duplicateLead->phone ||
            optional($currentLead->companyDetail)->contact_no == optional($duplicateLead->companyDetail)->contact_no)) {
            $matchTypes[] = 'company_phone';
        }

        return implode(', ', $matchTypes ?: ['unknown']);
    }
}
