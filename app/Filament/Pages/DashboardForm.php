<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;

use App\Classes\Encryptor;
use App\Enums\LeadCategoriesEnum;
use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\QuotationStatusEnum;
use App\Mail\DemoNotification;
use App\Mail\FollowUpNotification;
use App\Mail\SalespersonNotification;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\InvalidLeadReason;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Quotation;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use App\Services\QuotationService;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Twilio\Rest\Client;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model as MicrosoftGraph;
use Microsoft\Graph\Model\Event;
class DashboardForm extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Dashboard';
    protected static string $view = 'filament.pages.dashboard-form';
    public $users; // List of users to select from
    public $selectedUser; // Selected user's ID
    public $selectedUserRole;
    public $assignToMeModalVisible = false;
    public $currentLeadId;
    protected static string $relationship = 'activityLogs';

    // For "New Leads" table
    public $sortColumnNewLeads = 'created_at';
    public $sortDirectionNewLeads = 'desc';

    // For "My Pending Tasks" table
    public $sortColumnPendingTasks = 'created_at';
    public $sortDirectionPendingTasks = 'desc';

    public $sortColumnProspect = 'created_at';
    public $sortDirectionProspect = 'desc';

    public $sortColumnProspectOverdue = 'created_at';
    public $sortDirectionProspectOverdue = 'desc';

    public $sortColumnActiveBigCompanyLeads = 'created_at'; // Default sorting column
    public $sortDirectionActiveBigCompanyLeads = 'desc'; // Default sorting direction

    public $sortColumnActiveSmallCompanyLeads = 'created_at'; // Default sorting column
    public $sortDirectionActiveSmallCompanyLeads = 'desc'; // Default sorting direction

    public $sortColumnFollowUpSmallCompanyLeads = 'created_at'; // Default sorting column
    public $sortDirectionFollowUpSmallCompanyLeads = 'desc'; // Default sorting direction

    public $sortColumnFollowUpBigCompanyLeads = 'created_at'; // Default sorting column
    public $sortDirectionFollowUpBigCompanyLeads = 'desc'; // Default sorting direction

    public $sortColumnActiveSmallCompanyLeadsWithSalesperson = 'created_at'; // Default sorting column
    public $sortDirectionActiveSmallCompanyLeadsWithSalesperson = 'desc'; // Default sorting direction

    public $sortColumnActiveBigCompanyLeadsWithSalesperson = 'created_at'; // Default sorting column
    public $sortDirectionActiveBigCompanyLeadsWithSalesperson = 'desc'; // Default sorting direction

    public $sortColumnInactiveSmallCompanyLeads = 'created_at'; // Default sorting column
    public $sortDirectionInactiveSmallCompanyLeads = 'desc'; // Default sorting direction

    public $sortColumnInactiveBigCompanyLeads = 'created_at'; // Default sorting column
    public $sortDirectionInactiveBigCompanyLeads = 'desc'; // Default sorting direction

    public function mount()
    {
        $this->users = User::whereIn('role_id', [1, 2])->get(); // Fetch users with roles 1 and 2
    }

    public function sortBy($column, $table)
    {
        if ($table === 'newLeads') {
            if ($this->sortColumnNewLeads === $column) {
                $this->sortDirectionNewLeads = $this->sortDirectionNewLeads === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortColumnNewLeads = $column;
                $this->sortDirectionNewLeads = 'asc';
            }
        }

        if ($table === 'pendingTasks') {
            if ($this->sortColumnPendingTasks === $column) {
                $this->sortDirectionPendingTasks = $this->sortDirectionPendingTasks === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortColumnPendingTasks = $column;
                $this->sortDirectionPendingTasks = 'asc';
            }
        }

        if ($table === 'prospectToday') {
            if ($this->sortColumnProspect === $column) {
                $this->sortDirectionProspect = $this->sortDirectionProspect === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortColumnProspect = $column;
                $this->sortDirectionProspect = 'asc';
            }
        }

        if ($table === 'prospectOverdue') {
            if ($this->sortColumnProspectOverdue === $column) {
                $this->sortDirectionProspectOverdue = $this->sortDirectionProspectOverdue === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortColumnProspectOverdue = $column;
                $this->sortDirectionProspectOverdue = 'asc';
            }
        }

        if ($table === 'activeBigCompanyLeads') {
            if ($this->sortColumnActiveBigCompanyLeads === $column) {
                $this->sortDirectionActiveBigCompanyLeads = $this->sortDirectionActiveBigCompanyLeads === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortColumnActiveBigCompanyLeads = $column;
                $this->sortDirectionActiveBigCompanyLeads = 'asc';
            }
        }

        if ($table === 'activeSmallCompanyLeads') {
            if ($this->sortColumnActiveSmallCompanyLeads === $column) {
                $this->sortDirectionActiveSmallCompanyLeads = $this->sortDirectionActiveSmallCompanyLeads === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortColumnActiveSmallCompanyLeads = $column;
                $this->sortDirectionActiveSmallCompanyLeads = 'asc';
            }
        }

        if ($table === 'followUpSmallCompanyLeads') {
            if ($this->sortColumnFollowUpSmallCompanyLeads === $column) {
                $this->sortDirectionFollowUpSmallCompanyLeads = $this->sortDirectionFollowUpSmallCompanyLeads === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortColumnFollowUpSmallCompanyLeads = $column;
                $this->sortDirectionFollowUpSmallCompanyLeads = 'asc';
            }
        }

        if ($table === 'followUpBigCompanyLeads') {
            if ($this->sortColumnFollowUpBigCompanyLeads === $column) {
                $this->sortDirectionFollowUpBigCompanyLeads = $this->sortDirectionFollowUpBigCompanyLeads === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortColumnFollowUpBigCompanyLeads = $column;
                $this->sortDirectionFollowUpBigCompanyLeads = 'asc';
            }
        }

        if ($table === 'activeSmallCompanyLeadsWithSalesperson') {
            if ($this->sortColumnActiveSmallCompanyLeadsWithSalesperson === $column) {
                $this->sortDirectionActiveSmallCompanyLeadsWithSalesperson = $this->sortDirectionActiveSmallCompanyLeadsWithSalesperson === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortColumnActiveSmallCompanyLeadsWithSalesperson = $column;
                $this->sortDirectionActiveSmallCompanyLeadsWithSalesperson = 'asc';
            }
        }

        if ($table === 'activeBigCompanyLeadsWithSalesperson') {
            if ($this->sortColumnActiveBigCompanyLeadsWithSalesperson === $column) {
                $this->sortDirectionActiveBigCompanyLeadsWithSalesperson = $this->sortDirectionActiveBigCompanyLeadsWithSalesperson === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortColumnActiveBigCompanyLeadsWithSalesperson = $column;
                $this->sortDirectionActiveBigCompanyLeadsWithSalesperson = 'asc';
            }
        }

        if ($table === 'inactiveSmallCompanyLeads') {
            if ($this->sortColumnInactiveSmallCompanyLeads === $column) {
                $this->sortDirectionInactiveSmallCompanyLeads = $this->sortDirectionInactiveSmallCompanyLeads === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortColumnInactiveSmallCompanyLeads = $column;
                $this->sortDirectionInactiveSmallCompanyLeads = 'asc';
            }
        }

        if ($table === 'inactiveBigCompanyLeads') {
            if ($this->sortColumnInactiveBigCompanyLeads === $column) {
                $this->sortDirectionInactiveBigCompanyLeads = $this->sortDirectionInactiveBigCompanyLeads === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortColumnInactiveBigCompanyLeads = $column;
                $this->sortDirectionInactiveBigCompanyLeads = 'asc';
            }
        }
    }

    public $currentDashboard = 'LeadOwner';

    public function toggleDashboard($dashboard)
    {
        $this->currentDashboard = $dashboard;
    }

    public function updatedSelectedUser($userId)
    {
        $selectedUser = User::find($userId);

        if ($selectedUser) {
            $this->selectedUserRole = $selectedUser->role_id; // Update the selected user's role
        } else {
            $this->selectedUserRole = null; // Reset if no user is selected
        }

        // Add additional logic for live updates here
    }

    public function handleSelectedUser()
    {
        $this->updatedSelectedUser($this->selectedUser); // Manually call the update method
    }

    // New Leads Table
    public function getPendingLeadsQuery()
    {
        return Lead::query()
            ->where('categories', 'New') // Filter only new leads
            ->when($this->sortColumnNewLeads === 'companyDetail.company_name', function ($query) {
                return $query->leftJoin('company_details', 'leads.company_id', '=', 'company_details.id')
                    ->orderBy('company_details.company_name', $this->sortDirectionNewLeads);
            })
            ->when($this->sortColumnNewLeads === 'company_size', function ($query) {
                return $query->orderByRaw("
                    CASE
                        WHEN company_size = '1-24' THEN 1
                        WHEN company_size = '25-99' THEN 2
                        WHEN company_size = '100-500' THEN 3
                        WHEN company_size = '501 and above' THEN 4
                        ELSE 5
                    END " . $this->sortDirectionNewLeads);
            })
            ->when(!in_array($this->sortColumnNewLeads, ['companyDetail.company_name', 'company_size']), function ($query) {
                return $query->orderBy($this->sortColumnNewLeads, $this->sortDirectionNewLeads);
            })
            ->orderBy('created_at', 'desc'); // Default sorting by latest created leads
    }

    // My Pending Leads Table
    public function getNewLeadsQuery()
    {
        $leadOwner = auth()->user()->role_id == 3 && $this->selectedUser
            ? User::find($this->selectedUser)->name
            : auth()->user()->name;

        return Lead::query()
            ->where('stage', 'Transfer')
            ->where('lead_owner', $leadOwner)
            ->where('lead_status', 'New')
            ->selectRaw('*, DATEDIFF(updated_at, created_at) as pending_days') // Calculate pending days
            ->when($this->sortColumnPendingTasks === 'company_size', function ($query) {
                return $query->orderByRaw("
                    CASE
                        WHEN company_size = '1-24' THEN 1
                        WHEN company_size = '25-99' THEN 2
                        WHEN company_size = '100-500' THEN 3
                        WHEN company_size = '501 and above' THEN 4
                        ELSE 5
                    END " . $this->sortDirectionPendingTasks);
            })
            ->when($this->sortColumnPendingTasks === 'created_at', function ($query) {
                return $query->orderBy('created_at', $this->sortDirectionPendingTasks);
            })
            ->when($this->sortColumnPendingTasks === 'pending_days', function ($query) {
                return $query->orderBy('pending_days', $this->sortDirectionPendingTasks);
            })
            ->orderBy('created_at', 'desc');
    }

    // Prospect Reminder (Today)
    public function getProspectTodayQuery()
    {
        $leadOwner = auth()->user()->role_id == 3 && $this->selectedUser
            ? User::find($this->selectedUser)->name
            : auth()->user()->name;

        return Lead::query()
            ->whereDate('follow_up_date', today())
            ->where('lead_owner', $leadOwner)
            ->whereNull('salesperson')
            ->when($this->sortColumnProspect === 'company_size', function ($query) {
                return $query->orderByRaw("
                    CASE
                        WHEN company_size = '1-24' THEN 1
                        WHEN company_size = '25-99' THEN 2
                        WHEN company_size = '100-500' THEN 3
                        WHEN company_size = '501 and above' THEN 4
                        ELSE 5
                    END " . $this->sortDirectionProspect);
            })
            ->when($this->sortColumnProspect === 'created_at', function ($query) {
                return $query->orderBy('created_at', $this->sortDirectionProspect);
            })
            ->orderBy('follow_up_date', 'asc'); // Default sorting by follow-up date
    }

    public function getProspectOverdueQuery()
    {
        $leadOwner = auth()->user()->role_id == 3 && $this->selectedUser
            ? User::find($this->selectedUser)->name
            : auth()->user()->name;

        return Lead::query()
            ->whereDate('follow_up_date', '<', today()) // Overdue follow-ups
            ->where('lead_owner', $leadOwner) // Filter by authenticated user's name
            ->whereNull('salesperson') // Ensure salesperson is NULL
            ->selectRaw('*, DATEDIFF(COALESCE(updated_at, NOW()), created_at) as pending_days_prospect_overdue')
            ->when($this->sortColumnProspectOverdue === 'pending_days_prospect_overdue', function ($query) {
                return $query->orderBy('pending_days_prospect_overdue', $this->sortDirectionProspectOverdue);
            })
            ->when($this->sortColumnProspectOverdue === 'company_size', function ($query) {
                return $query->orderByRaw("
                    CASE
                        WHEN company_size = '1-24' THEN 1
                        WHEN company_size = '25-99' THEN 2
                        WHEN company_size = '100-500' THEN 3
                        WHEN company_size = '501 and above' THEN 4
                        ELSE 5
                    END " . $this->sortDirectionProspectOverdue);
            })
            ->when($this->sortColumnProspectOverdue === 'created_at', function ($query) {
                return $query->orderBy('created_at', $this->sortDirectionProspectOverdue);
            })
            ->orderBy('follow_up_date', 'asc'); // Default sorting by follow-up date
    }

    public function resetDoneCall()
    {
        DB::beginTransaction(); // Start transaction

        try {
            $affectedRows = Lead::where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->whereBetween('call_attempt', [1, 10])
                ->where('company_size', '=', '1-24')
                ->update(['done_call' => 0]);

            // If no leads were updated, show a warning
            if ($affectedRows === 0) {
                Notification::make()
                    ->title('No Done Calls Were Reset')
                    ->warning()
                    ->send();
                DB::rollBack(); // Rollback since nothing changed
                return;
            }

            DB::commit(); // Commit transaction

            // Show success notification
            Notification::make()
                ->title('Done Calls Reset Successfully')
                ->success()
                ->send();

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on failure

            Notification::make()
                ->title('Error Resetting Done Calls')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function resetBigCompanyDoneCall()
    {
        DB::beginTransaction(); // Start transaction

        try {
            $affectedRows = Lead::where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->whereBetween('call_attempt', [1, 10])
                ->where('company_size', '!=', '1-24')
                ->update(['done_call' => 0]);

            // If no leads were updated, show a warning
            if ($affectedRows === 0) {
                Notification::make()
                    ->title('No Done Calls Were Reset')
                    ->warning()
                    ->send();
                DB::rollBack(); // Rollback since nothing changed
                return;
            }

            DB::commit(); // Commit transaction

            // Show success notification
            Notification::make()
                ->title('Done Calls Reset Successfully')
                ->success()
                ->send();

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on failure

            Notification::make()
                ->title('Error Resetting Done Calls')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function assignLead($leadId)
    {
        $lead = Lead::findOrFail($leadId);

        if (is_null($lead->lead_owner)) {
            $lead->update([
                'lead_owner' => auth()->user()->name,
                'categories' => 'Active',
                'stage' => 'Transfer',
                'lead_status' => 'New',
            ]);

            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                ->orderByDesc('created_at')
                ->first();

            if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Lead Owner: ' . auth()->user()->name) {
                $latestActivityLog->update([
                    'description' => 'Lead assigned to Lead Owner: ' . auth()->user()->name,
                ]);
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($lead);
            }

            Notification::make()
                ->title('Lead Owner Assigned Successfully')
                ->success()
                ->send();
        }
        $this->assignToMeModalVisible = false; // Hide the modal
    }

    public function assignLeadToUser($leadId)
    {
        $lead = Lead::findOrFail($leadId);

        if (!is_null($this->selectedUser)) {
            $selectedUser = User::findOrFail($this->selectedUser); // Find the selected user

            $lead->update([
                'lead_owner' => $selectedUser->name, // Assign the lead owner to the selected user
                'categories' => 'Active',
                'stage' => 'Transfer',
                'lead_status' => 'New',
            ]);

            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                ->orderByDesc('created_at')
                ->first();

            if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Lead Owner: ' . $selectedUser->name) {
                $latestActivityLog->update([
                    'description' => 'Lead assigned to Lead Owner: ' . $selectedUser->name,
                ]);
            } else {
                ActivityLog::create([
                    'description' => 'Lead assigned to Lead Owner: ' . $selectedUser->name,
                    'subject_id' => $lead->id,
                    'causer_id' => auth()->id(),
                ]);
            }

            activity()
                ->causedBy(auth()->user())
                ->performedOn($lead);

            Notification::make()
                ->title('Lead assigned to Lead Owner: ' . $selectedUser->name)
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('No User Selected')
                ->warning()
                ->send();
        }
    }

    //Salesperson's Dashboard
    public function getTodayDemos()
    {
        $salespersonId = auth()->user()->role_id == 3 && $this->selectedUser ? $this->selectedUser : auth()->id();

        return Lead::whereHas('demoAppointment', function ($query) use ($salespersonId) {
                $query->whereDate('date', today()) // Filter for today's appointment date
                    ->where('salesperson', $salespersonId) // Filter by authenticated user's ID
                    ->where('status', 'new'); // Filter for status 'new'
            });
    }

    public function getTomorrowDemos()
    {
        $salespersonId = auth()->user()->role_id == 3 && $this->selectedUser ? $this->selectedUser : auth()->id();

        return Lead::whereHas('demoAppointment', function ($query) use ($salespersonId) {
            $query->whereDate('date', today()->addDay()) // Filter for today's appointment date
                ->where('salesperson', $salespersonId) // Filter by authenticated user's ID
                ->where('status', 'new'); // Filter for status 'new'
        });
    }

    public function getTodayProspects()
    {
        $salespersonId = auth()->user()->role_id == 3 && $this->selectedUser ? $this->selectedUser : auth()->id();

        return Lead::query()
            ->where('salesperson', $salespersonId) // Filter by salesperson
            ->whereDate('follow_up_date', today());
    }

    public function getOverdueProspects()
    {
        $salespersonId = auth()->user()->role_id == 3 && $this->selectedUser ? $this->selectedUser : auth()->id();

        return Lead::query()
            ->where('salesperson', $salespersonId) // Filter by salesperson
            ->whereDate('follow_up_date', '<', today());
    }

    public function getTodayDebtors()
    {
        return Lead::where('lead_status', 'debtor')
            ->whereDate('follow_up_date', today())
            ->get();
    }

    public function getOverdueDebtors()
    {
        return Lead::where('lead_status', 'debtor')
            ->whereDate('follow_up_date', '<', today())
            ->get();
    }

    //Active, Follow Up, Salesperson, Invalid Table
    public function getActiveSmallCompanyLeads()
    {
        return Lead::query()
            ->where('company_size', '=', '1-24') // Match exact '1-24'
            ->whereNull('salesperson') // Salesperson must be NULL
            ->whereNotNull('lead_owner')
            ->where('categories', '!=', 'Inactive') // Exclude Inactive leads
            ->where(function ($query) {
                $query->whereNull('done_call') // Include NULL values
                    ->orWhere('done_call', 0); // Include 0 values
            })
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_time') // Calculate Pending Days
            ->when($this->sortColumnActiveSmallCompanyLeads === 'pending_time', function ($query) {
                return $query->orderBy('pending_time', $this->sortDirectionActiveSmallCompanyLeads);
            })
            ->when($this->sortColumnActiveSmallCompanyLeads === 'company_size', function ($query) {
                return $query->orderByRaw("
                    CASE
                        WHEN company_size = '1-24' THEN 1
                        WHEN company_size = '25-99' THEN 2
                        WHEN company_size = '100-500' THEN 3
                        WHEN company_size = '501 and above' THEN 4
                        ELSE 5
                    END " . $this->sortDirectionActiveSmallCompanyLeads);
            })
            ->when($this->sortColumnActiveSmallCompanyLeads === 'call_attempt', function ($query) {
                return $query->orderBy('call_attempt', $this->sortDirectionActiveSmallCompanyLeads);
            })
            ->orderBy($this->sortColumnActiveSmallCompanyLeads, $this->sortDirectionActiveSmallCompanyLeads) // Default sorting
            ->get();
    }

    public function getActiveBigCompanyLeads()
    {
        return Lead::query()
            ->where('company_size', '!=', '1-24') // Exclude small companies
            ->whereNull('salesperson') // Salesperson must be NULL
            ->whereNotNull('lead_owner')
            ->where('categories', '!=', 'Inactive') // Exclude Inactive leads
            ->where(function ($query) {
                $query->whereNull('done_call') // Include NULL values
                    ->orWhere('done_call', 0); // Include 0 values
            })
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_time') // Calculate Pending Days
            ->when($this->sortColumnActiveBigCompanyLeads === 'pending_time', function ($query) {
                return $query->orderBy('pending_time', $this->sortDirectionActiveBigCompanyLeads);
            })
            ->when($this->sortColumnActiveBigCompanyLeads === 'company_size', function ($query) {
                return $query->orderByRaw("
                    CASE
                        WHEN company_size = '1-24' THEN 1
                        WHEN company_size = '25-99' THEN 2
                        WHEN company_size = '100-500' THEN 3
                        WHEN company_size = '501 and above' THEN 4
                        ELSE 5
                    END " . $this->sortDirectionActiveBigCompanyLeads);
            })
            ->when($this->sortColumnActiveBigCompanyLeads === 'call_attempts', function ($query) {
                return $query->orderBy('call_attempts', $this->sortDirectionActiveBigCompanyLeads);
            })
            ->orderBy($this->sortColumnActiveBigCompanyLeads, $this->sortDirectionActiveBigCompanyLeads) // Default sorting
            ->get();
    }

    public function getFollowUpSmallCompanyLeads()
    {
        return Lead::query()
            ->where('done_call', '=', '1')
            ->whereNull('salesperson') // Salesperson is NULL
            ->whereBetween('call_attempt', [1, 10])
            ->where('company_size', '=', '1-24') // Only small companies (1-24)
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_time') // Calculate Pending Time
            ->when($this->sortColumnFollowUpSmallCompanyLeads === 'pending_time', function ($query) {
                return $query->orderBy('pending_time', $this->sortDirectionFollowUpSmallCompanyLeads);
            })
            ->when($this->sortColumnFollowUpSmallCompanyLeads === 'company_size', function ($query) {
                return $query->orderByRaw("
                    CASE
                        WHEN company_size = '1-24' THEN 1
                        WHEN company_size = '25-99' THEN 2
                        WHEN company_size = '100-500' THEN 3
                        WHEN company_size = '501 and above' THEN 4
                        ELSE 5
                    END " . $this->sortDirectionFollowUpSmallCompanyLeads);
            })
            ->when($this->sortColumnFollowUpSmallCompanyLeads === 'call_attempt', function ($query) {
                return $query->orderBy('call_attempt', $this->sortDirectionFollowUpSmallCompanyLeads);
            })
            ->orderBy($this->sortColumnFollowUpSmallCompanyLeads, $this->sortDirectionFollowUpSmallCompanyLeads) // Default sorting
            ->get();
    }

    public function getFollowUpBigCompanyLeads()
    {
        return Lead::query()
            ->where('done_call', '=', '1')
            ->whereNull('salesperson') // Salesperson is NULL
            ->whereBetween('call_attempt', [1, 10])
            ->where('company_size', '!=', '1-24') // Exclude small companies (1-24)
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_time') // Calculate Pending Time
            ->when($this->sortColumnFollowUpBigCompanyLeads === 'pending_time', function ($query) {
                return $query->orderBy('pending_time', $this->sortDirectionFollowUpBigCompanyLeads);
            })
            ->when($this->sortColumnFollowUpBigCompanyLeads === 'company_size', function ($query) {
                return $query->orderByRaw("
                    CASE
                        WHEN company_size = '1-24' THEN 1
                        WHEN company_size = '25-99' THEN 2
                        WHEN company_size = '100-500' THEN 3
                        WHEN company_size = '501 and above' THEN 4
                        ELSE 5
                    END " . $this->sortDirectionFollowUpBigCompanyLeads);
            })
            ->when($this->sortColumnFollowUpBigCompanyLeads === 'call_attempt', function ($query) {
                return $query->orderBy('call_attempt', $this->sortDirectionFollowUpBigCompanyLeads);
            })
            ->orderBy($this->sortColumnFollowUpBigCompanyLeads, $this->sortDirectionFollowUpBigCompanyLeads) // Default sorting
            ->get();
    }

    public function getActiveSmallCompanyLeadsWithSalesperson()
    {
        return Lead::query()
            ->whereNotNull('salesperson') // Ensure salesperson is NOT NULL
            ->where('company_size', '=', '1-24') // Only small companies (1-24)
            ->where('categories', '!=', 'Inactive') // Exclude Inactive leads
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_time') // Calculate Pending Time
            ->when($this->sortColumnActiveSmallCompanyLeadsWithSalesperson === 'pending_time', function ($query) {
                return $query->orderBy('pending_time', $this->sortDirectionActiveSmallCompanyLeadsWithSalesperson);
            })
            ->when($this->sortColumnActiveSmallCompanyLeadsWithSalesperson === 'salesperson', function ($query) {
                return $query->orderBy('salesperson', $this->sortDirectionActiveSmallCompanyLeadsWithSalesperson);
            })
            ->when($this->sortColumnActiveSmallCompanyLeadsWithSalesperson === 'company_size', function ($query) {
                return $query->orderByRaw("
                    CASE
                        WHEN company_size = '1-24' THEN 1
                        WHEN company_size = '25-99' THEN 2
                        WHEN company_size = '100-500' THEN 3
                        WHEN company_size = '501 and above' THEN 4
                        ELSE 5
                    END " . $this->sortDirectionActiveSmallCompanyLeadsWithSalesperson);
            })
            ->when($this->sortColumnActiveSmallCompanyLeadsWithSalesperson === 'stage', function ($query) {
                return $query->orderBy('stage', $this->sortDirectionActiveSmallCompanyLeadsWithSalesperson);
            })
            ->orderBy($this->sortColumnActiveSmallCompanyLeadsWithSalesperson, $this->sortDirectionActiveSmallCompanyLeadsWithSalesperson) // Default sorting
            ->get();
    }

    public function getActiveBigCompanyLeadsWithSalesperson()
    {
        return Lead::query()
            ->whereNotNull('salesperson') // Ensure salesperson is NOT NULL
            ->where('company_size', '!=', '1-24') // Exclude small companies (1-24)
            ->where('categories', '!=', 'Inactive') // Exclude Inactive leads
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_days') // Calculate Pending Days
            ->when($this->sortColumnActiveBigCompanyLeadsWithSalesperson === 'pending_days', function ($query) {
                return $query->orderBy('pending_days', $this->sortDirectionActiveBigCompanyLeadsWithSalesperson);
            })
            ->when($this->sortColumnActiveBigCompanyLeadsWithSalesperson === 'company_size', function ($query) {
                return $query->orderByRaw("
                    CASE
                        WHEN company_size = '1-24' THEN 1
                        WHEN company_size = '25-99' THEN 2
                        WHEN company_size = '100-500' THEN 3
                        WHEN company_size = '501 and above' THEN 4
                        ELSE 5
                    END " . $this->sortDirectionActiveBigCompanyLeadsWithSalesperson);
            })
            ->when($this->sortColumnActiveBigCompanyLeadsWithSalesperson === 'stage', function ($query) {
                return $query->orderBy('stage', $this->sortDirectionActiveBigCompanyLeadsWithSalesperson);
            })
            ->orderBy($this->sortColumnActiveBigCompanyLeadsWithSalesperson, $this->sortDirectionActiveBigCompanyLeadsWithSalesperson) // Default sorting
            ->get();
    }

    public function getInactiveSmallCompanyLeads()
    {
        return Lead::query()
            ->where('categories', 'Inactive') // Only Inactive leads
            ->where('company_size', '=', '1-24') // Only small companies (1-24)
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_days') // Calculate Pending Days
            ->when($this->sortColumnInactiveSmallCompanyLeads === 'pending_days', function ($query) {
                return $query->orderBy('pending_days', $this->sortDirectionInactiveSmallCompanyLeads);
            })
            ->when($this->sortColumnInactiveSmallCompanyLeads === 'company_size', function ($query) {
                return $query->orderByRaw("
                    CASE
                        WHEN company_size = '1-24' THEN 1
                        WHEN company_size = '25-99' THEN 2
                        WHEN company_size = '100-500' THEN 3
                        WHEN company_size = '501 and above' THEN 4
                        ELSE 5
                    END " . $this->sortDirectionInactiveSmallCompanyLeads);
            })
            ->when($this->sortColumnInactiveSmallCompanyLeads === 'lead_status', function ($query) {
                return $query->orderBy('lead_status', $this->sortDirectionInactiveSmallCompanyLeads);
            })
            ->orderBy($this->sortColumnInactiveSmallCompanyLeads, $this->sortDirectionInactiveSmallCompanyLeads) // Default sorting
            ->get();
    }

    public function getInactiveBigCompanyLeads()
    {
        return Lead::query()
            ->where('categories', 'Inactive') // Only Inactive leads
            ->where('company_size', '!=', '1-24') // Exclude small companies (1-24)
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_days') // Calculate Pending Days
            ->when($this->sortColumnInactiveBigCompanyLeads === 'pending_days', function ($query) {
                return $query->orderBy('pending_days', $this->sortDirectionInactiveBigCompanyLeads);
            })
            ->when($this->sortColumnInactiveBigCompanyLeads === 'company_size', function ($query) {
                return $query->orderByRaw("
                    CASE
                        WHEN company_size = '1-24' THEN 1
                        WHEN company_size = '25-99' THEN 2
                        WHEN company_size = '100-500' THEN 3
                        WHEN company_size = '501 and above' THEN 4
                        ELSE 5
                    END " . $this->sortDirectionInactiveBigCompanyLeads);
            })
            ->when($this->sortColumnInactiveBigCompanyLeads === 'lead_status', function ($query) {
                return $query->orderBy('lead_status', $this->sortDirectionInactiveBigCompanyLeads);
            })
            ->orderBy($this->sortColumnInactiveBigCompanyLeads, $this->sortDirectionInactiveBigCompanyLeads) // Default sorting
            ->get();
    }
}
