<?php

namespace App\Livewire\HrAdminDashboard;

use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Collection;
use Livewire\Component;

class CompanyUsersTab extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public ?int $softwareHandoverId = null;
    public array $companyData = [];
    public Collection $users;

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadUsers();
    }

    protected function loadUsers(): void
    {
        // Mock data for development - will be replaced with API integration
        $this->users = collect([
            [
                'id' => 1,
                'backend_user_id' => $this->companyData['hr_user_id'] ?? '496677',
                'full_name' => 'John Doe',
                'login_id' => 'john.doe@company.com',
                'role' => 'OWNER',
                'status' => 'Active',
                'ta' => true,
                'tl' => true,
                'tc' => false,
                'tp' => true,
                'to' => true,
                'tr' => false,
                'tap' => true,
                'tt' => false,
            ],
            [
                'id' => 2,
                'backend_user_id' => '496678',
                'full_name' => 'Jane Smith',
                'login_id' => 'jane.smith@company.com',
                'role' => 'USER',
                'status' => 'Active',
                'ta' => true,
                'tl' => true,
                'tc' => true,
                'tp' => false,
                'to' => false,
                'tr' => true,
                'tap' => false,
                'tt' => true,
            ],
            [
                'id' => 3,
                'backend_user_id' => '496679',
                'full_name' => 'Bob Johnson',
                'login_id' => 'bob.johnson@company.com',
                'role' => 'USER',
                'status' => 'Inactive',
                'ta' => true,
                'tl' => false,
                'tc' => false,
                'tp' => true,
                'to' => true,
                'tr' => false,
                'tap' => true,
                'tt' => false,
            ],
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Using a query builder with the collection as a workaround
                \App\Models\HrLicense::query()->whereRaw('1 = 0') // Empty query as placeholder
            )
            ->emptyState(fn () => view('livewire.hr-admin-dashboard.company-users-tab-content', ['users' => $this->users]))
            ->columns([])
            ->paginated(false);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-users-tab');
    }
}
