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
use App\Filament\Resources\LeadResource\Widgets\NewLeadTable;
use App\Filament\Resources\LeadResource\Widgets\PendingLeadTable;
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
use Filament\Widgets\Concerns\InteractsWithPageTable;
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
    use InteractsWithPageTable;

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
        $this->selectedUser = $userId; // Store selected user
        $selectedUser = User::find($userId);

        if ($selectedUser) {
            $this->selectedUserRole = $selectedUser->role_id;

            // Use toggleDashboard to switch dashboards dynamically
            $this->toggleDashboard($this->selectedUserRole == 2 ? 'Salesperson' : 'LeadOwner');
        } else {
            $this->selectedUserRole = null;
            $this->toggleDashboard('LeadOwner'); // Default to LeadOwner if no user selected
        }

        session(['selectedUser' => $userId]); // Store the selected user in session
        $this->dispatch('updateTablesForUser', selectedUser: $userId); // Dispatch event
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
