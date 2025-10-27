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
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.dashboard-form';
    public $users; // List of users to select from
    public $selectedUser; // Selected user's ID
    public $selectedUserRole;
    public $selectedUserModel;
    public $assignToMeModalVisible = false;
    public $currentLeadId;
    public $selectedAdditionalRole;
    public $lastRefreshTime;

    public function refreshTable()
    {
        // Update timestamp
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        // Dispatch events to refresh child components
        $this->dispatch('refresh-implementer-tables');
        $this->dispatch('refresh-leadowner-tables');
        $this->dispatch('refresh-softwarehandover-tables');
        $this->dispatch('refresh-hardwarehandover-tables');
        $this->dispatch('refresh-salesperson-tables');
        $this->dispatch('refresh-adminrepair-tables');
        $this->dispatch('refresh-manager-tables');

        // Force Alpine components to reset
        $this->dispatch('forceResetDashboards');

        // Show notification
        Notification::make()
            ->title('Dashboard refreshed')
            ->success()
            ->send();
    }

    public function mount()
    {
        $this->users = User::whereIn('role_id', [1, 2, 4, 5])->get(); // Fetch users with roles 1 and 2
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        $currentUser = auth()->user();
        $defaultDashboard = match($currentUser->role_id) {
            1 => 'LeadOwner',
            2 => 'Salesperson',
            3 => 'Manager',
            4 => 'Implementer',
            5 => 'Implementer',
            default => 'LeadOwner',
        };

        // Set default to LeadOwner
        $this->currentDashboard = session('currentDashboard', 'LeadOwner');

        // Default selectedUser to 7 (Your Own Dashboard) when the page loads
        $this->selectedUser = session('selectedUser') == 7;
        session(['selectedUser' => $this->selectedUser]); // Store it in session

        // Initialize selectedUserModel for the current user
        $this->selectedUserModel = $currentUser;

        // Initialize additional role from session
        $this->selectedAdditionalRole = session('selectedAdditionalRole');

        if (request()->has('page') && request()->get('page') != 1) {
            return redirect()->to(url()->current() . '?page=1');
        }
    }

    public $currentDashboard = 'LeadOwner';

    public function toggleDashboard($dashboard)
    {
        // Add the new dashboard options to the valid list
        $validDashboards = [
            'LeadOwner',
            'Salesperson',
            'Manager',
            'SoftwareHandover',
            'HardwareHandover',
            'MainAdminDashboard',
            'SoftwareAdmin',
            'HardwareAdmin',
            'HardwareAdminV2',
            'AdminRepair',
            'Training',
            'Finance',
            'HRDF',
            'AdminRenewalv1',
            'AdminRenewalv2',
            'AdminHRDF',
            'AdminHeadcount',
            // 'AdminUSDInvoice',
            'General',
            'Credit Controller',
            'Trainer',
            'Implementer',
            'Support',
            'Technician',
            'Debtor'
        ];

        if (in_array($dashboard, $validDashboards)) {
            $this->currentDashboard = $dashboard;
            session(['currentDashboard' => $dashboard]);

            // For users with additional_role=1, update their view accordingly
            if (isset($this->selectedUserModel) && $this->selectedUserModel &&
                $this->selectedUserModel->role_id == 1 && $this->selectedUserModel->additional_role == 1) {
                // Store the selected dashboard view for this user
                session(['selectedUserDashboard_' . $this->selectedUserModel->id => $dashboard]);
            }

            // Force a UI refresh - dispatch Livewire event only
            $this->dispatch('dashboard-changed', ['dashboard' => $dashboard]);
        }
    }

    public function updatedSelectedUser($userId)
    {
        $this->selectedUser = $userId;
        session(['selectedUser' => $userId]);

        if (in_array($userId, ['all-lead-owners', 'all-salespersons', 'all-implementer'])) {
            $this->selectedUserRole =
                $userId === 'all-salespersons' ? 2 :
                ($userId === 'all-implementer' ? 4 : 1);
            $this->selectedUserModel = null; // No specific user model for group selections
            $this->toggleDashboard($this->selectedUserRole === 2 ? 'Salesperson' :
                ($this->selectedUserRole === 4 ? 'Implementer' : 'LeadOwner'));
        } else {
            $selectedUser = User::find($userId);

            if ($selectedUser) {
                $this->selectedUserModel = $selectedUser; // Store the selected user model
                $this->selectedUserRole = $selectedUser->role_id;

                // Change dashboard based on role and additional_role if applicable
                if ($selectedUser->role_id == 1 && $selectedUser->additional_role == 1) {
                    $this->toggleDashboard('SoftwareHandover'); // Or choose an appropriate default
                } else {
                    $this->toggleDashboard(match($selectedUser->role_id) {
                        1 => 'LeadOwner',
                        2 => 'Salesperson',
                        3 => 'Manager',
                        4, 5 => 'Implementer',
                        default => 'Manager',
                    });
                }
            } else {
                $this->selectedUserRole = null;
                $this->selectedUserModel = null;
                $this->toggleDashboard('Manager');
            }
        }

        $this->dispatch('updateTablesForUser', selectedUser: $userId);
    }

    public function updatedSelectedAdditionalRole($additionalRoleId)
    {
        $this->selectedAdditionalRole = $additionalRoleId;
        session(['selectedAdditionalRole' => $additionalRoleId]);

        if (in_array($additionalRoleId, ['implementer', 'sales-manager', 'team-lead'])) {
            // Handle specific predefined role groups
            switch ($additionalRoleId) {
                case 'implementer':
                    $this->toggleDashboard('Implementer');
                    break;
                case 'sales-manager':
                    $this->toggleDashboard('SalesManager');
                    break;
                case 'team-lead':
                    $this->toggleDashboard('TeamLead');
                    break;
                default:
                    $this->toggleDashboard('Manager');
            }
        } else {
            // Handle specific additional role IDs
            $role = \App\Models\Role::find($additionalRoleId);

            if ($role) {
                if ($role->name === 'Implementer') {
                    $this->toggleDashboard('Implementer');
                } elseif ($role->name === 'Sales Manager') {
                    $this->toggleDashboard('SalesManager');
                } elseif ($role->name === 'Team Lead') {
                    $this->toggleDashboard('TeamLead');
                } else {
                    // Default view for other roles
                    $this->toggleDashboard('Manager');
                }
            } else {
                // Fallback to default view
                $this->toggleDashboard('Manager');
            }
        }

        // Dispatch event to update tables based on the selected additional role
        $this->dispatch('updateTablesForAdditionalRole', selectedAdditionalRole: $additionalRoleId);
    }

    // New method for toggling between Lead Owner, Software Handover, and Hardware Handover
    public function toggleHandoverView($view)
    {
        $this->toggleDashboard($view);
        session(['currentDashboard' => $view]);
    }
}
