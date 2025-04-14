<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class DashboardForm extends Page
{
    use InteractsWithPageTable;

    protected static ?string $navigationIcon = 'heroicon-o-home';
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

        // Default selectedUser to 7 (Your Own Dashboard) when the page loads
        $this->selectedUser = session('selectedUser') == 7;
        session(['selectedUser' => $this->selectedUser]); // Store it in session

        if (request()->has('page') && request()->get('page') != 1) {
            return redirect()->to(url()->current() . '?page=1');
        }
    }

    public $currentDashboard = 'LeadOwner';

    public function toggleDashboard($dashboard)
    {
        $this->currentDashboard = $dashboard;
    }

    public function updatedSelectedUser($userId)
    {
        $this->selectedUser = $userId;
        session(['selectedUser' => $userId]);

        if (in_array($userId, ['all-lead-owners', 'all-salespersons'])) {
            // Set role based on group type
            $this->selectedUserRole = $userId === 'all-salespersons' ? 2 : 1;
            $this->toggleDashboard($this->selectedUserRole === 2 ? 'Salesperson' : 'LeadOwner');
        } else {
            $selectedUser = User::find($userId);

            if ($selectedUser) {
                $this->selectedUserRole = $selectedUser->role_id;
                $this->toggleDashboard($selectedUser->role_id == 2 ? 'Salesperson' : 'LeadOwner');
            } else {
                $this->selectedUserRole = null;
                $this->toggleDashboard('LeadOwner');
            }
        }

        $this->dispatch('updateTablesForUser', selectedUser: $userId);
    }
}
