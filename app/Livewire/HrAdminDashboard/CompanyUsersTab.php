<?php

namespace App\Livewire\HrAdminDashboard;

use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use App\Models\Customer;

class CompanyUsersTab extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public ?int $softwareHandoverId = null;
    public array $companyData = [];
    public Collection $users;

    public bool $showEditDrawer = false;
    public ?string $editLoginId = null;
    public string $newPassword = '';
    public string $confirmPassword = '';

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadUsers();
    }

    protected function loadUsers(): void
    {
        $customers = Customer::where('sw_id', $this->softwareHandoverId)->get();

        $this->users = $customers->map(function ($customer, $index) {
            return [
                'id' => $customer->id,
                'backend_user_id' => $this->companyData['hr_user_id'] ?? '-',
                'full_name' => $customer->name ?? '-',
                'login_id' => $customer->email,
                'password' => $customer->plain_password ?? '-',
                'role' => $index === 0 ? 'OWNER' : 'USER',
                'status' => $customer->status ?? 'Active',
                'ta' => false,
                'tl' => false,
                'tc' => false,
                'tp' => false,
                'to' => false,
                'tr' => false,
                'tap' => false,
                'tt' => false,
            ];
        });
    }

    public function openEditDrawer(string $loginId): void
    {
        $this->editLoginId = $loginId;
        $this->newPassword = '';
        $this->confirmPassword = '';
        $this->resetValidation();
        $this->showEditDrawer = true;
    }

    public function closeEditDrawer(): void
    {
        $this->showEditDrawer = false;
        $this->editLoginId = null;
        $this->newPassword = '';
        $this->confirmPassword = '';
        $this->resetValidation();
    }

    public function updatePassword(): void
    {
        $this->validate([
            'newPassword' => 'required|min:6',
            'confirmPassword' => 'required|same:newPassword',
        ], [
            'confirmPassword.same' => 'The confirm password must match the new password.',
        ]);

        $customer = Customer::where('sw_id', $this->softwareHandoverId)
            ->where('email', $this->editLoginId)
            ->first();

        if (!$customer) {
            $this->addError('newPassword', 'Customer record not found for this login ID.');
            return;
        }

        $customer->forceFill([
            'password' => Hash::make($this->newPassword),
            'plain_password' => $this->newPassword,
        ])->save();

        $this->closeEditDrawer();

        Notification::make()
            ->success()
            ->title('Password updated successfully')
            ->send();
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
