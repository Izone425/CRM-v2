<?php
namespace App\Filament\Pages;

use App\Models\ActivityLog;
use Filament\Pages\Page;
use Filament\Tables;
use App\Models\Lead;
use App\Models\User;
use Filament\Notifications\Notification;

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

    public function mount()
    {
        $this->users = User::whereIn('role_id', [1, 2])->get(); // Fetch users with roles 1 and 2
    }

    public function showAssignToMeModal($leadId)
    {
        $this->currentLeadId = $leadId; // Store the lead ID
        $this->assignToMeModalVisible = true; // Show the modal
    }

    public function updatedSelectedUser($userId)
    {
        $selectedUser = User::find($userId);

        if ($selectedUser) {
            $this->selectedUserRole = $selectedUser->role_id; // Update the selected user's role
        } else {
            $this->selectedUserRole = null; // Reset if no user is selected
        }
    }

    public function handleSelectedUser()
    {
        $this->updatedSelectedUser($this->selectedUser); // Manually call the update method
    }

    // Pending Leads Table
    public function getPendingLeadsQuery()
    {
        return Lead::query()->where('categories', 'New');
    }

    // My New Leads Table
    public function getNewLeadsQuery()
    {
        $leadOwner = auth()->user()->role_id == 3 && $this->selectedUser
            ? User::find($this->selectedUser)->name
            : auth()->user()->name;

        return Lead::query()
            ->where('stage', 'Transfer') // Filter leads in the 'Transfer' stage
            ->where('lead_owner', $leadOwner) // Filter by the lead owner (dynamic)
            ->where('lead_status', 'New');
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
            ->orderBy('follow_up_date', 'asc');
    }

    // Prospect Reminder (Overdue)
    public function getProspectOverdueQuery()
    {
        $leadOwner = auth()->user()->role_id == 3 && $this->selectedUser
            ? User::find($this->selectedUser)->name
            : auth()->user()->name;

        return Lead::query()->whereDate('follow_up_date', '<', today())
            ->where('lead_owner', $leadOwner) // Filter by authenticated user's name
            ->whereNull('salesperson')
            ->orderBy('follow_up_date', 'asc');
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
}
