<?php
namespace App\Filament\Pages;

use App\Models\DevicePurchaseItem;
use App\Models\ShippingDeviceModel;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class DevicePurchaseInformation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Device Purchase Information';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $title = 'Device Purchase Information';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.device-purchase-information';

    public $selectedYear = '';
    public $months = [];
    public $editMode = false;
    public $expandedMonths = [];
    public $newModel = [];
    public $purchaseData = [];

    // Modal properties
    public $isModalOpen = false;
    public $editingMonth = null;
    public $editingModel = null;
    public $editingData = [];

    // Status update modal
    public $isStatusModalOpen = false;
    public $statusMonth = null;
    public $statusModel = null;
    public $selectedStatus = null;

    public $selectedMonth = null;

    public function mount()
    {
        $this->selectedYear = request()->query('year', Carbon::now()->year);
        $this->loadPurchaseData();
        $currentMonth = (int)date('n');

        // Select the current month by default
        $this->selectedMonth = $currentMonth;

    }

    public function selectMonth($monthNum)
    {
        $this->selectedMonth = $monthNum;
    }

    protected function getHeaderActions(): array
    {
        $years = range(Carbon::now()->year, Carbon::now()->year + 2);

        $actions = [];
        foreach ($years as $year) {
            $actions[] = Action::make("year_$year")
                ->label($year)
                ->url(fn() => route('filament.admin.pages.device-purchase-information', ['year' => $year]))
                ->color($year == $this->selectedYear ? 'primary' : 'warning');
        }

        return $actions;
    }

    public function toggleEditMode()
    {
        $this->editMode = !$this->editMode;
    }

    public function toggleMonth($month)
    {
        if (in_array($month, $this->expandedMonths)) {
            $this->expandedMonths = array_diff($this->expandedMonths, [$month]);
        } else {
            $this->expandedMonths[] = $month;
        }
    }

    // Open edit modal
    public function openEditModal($monthKey, $model)
    {
        $this->editingMonth = $monthKey;
        $this->editingModel = $model;
        $this->editingData = $this->purchaseData[$monthKey][$model];
        $this->isModalOpen = true;
    }

    // Open create modal
    public function openCreateModal($monthKey)
    {
        $this->editingMonth = $monthKey;
        $this->editingModel = null;
        $this->editingData = [
            'qty' => 0,
            'england' => 0,
            'america' => 0,
            'europe' => 0,
            'australia' => 0,
            'sn_no_from' => '',
            'sn_no_to' => '',
            'po_no' => '',
            'order_no' => '',
            'balance_not_order' => 0,
            'rfid_card_foc' => 0,
            'languages' => '',
            'features' => '',
            'model' => '',
            'status' => 'Completed Order',
        ];
        $this->isModalOpen = true;
    }

    // Close modal
    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->editingMonth = null;
        $this->editingModel = null;
        $this->editingData = [];
    }

    // Open status update modal
    public function openStatusModal($monthKey, $model)
    {
        $this->statusMonth = $monthKey;
        $this->statusModel = $model;
        $this->selectedStatus = $this->purchaseData[$monthKey][$model]['status'] ?? null;
        $this->isStatusModalOpen = true;
    }

    // Close status modal
    public function closeStatusModal()
    {
        $this->isStatusModalOpen = false;
        $this->statusMonth = null;
        $this->statusModel = null;
        $this->selectedStatus = null;
    }

    // Update status
    public function updateStatus()
    {
        try {
            $monthKey = $this->statusMonth;
            $modelName = $this->statusModel;

            // Find the purchase item
            $item = DevicePurchaseItem::where([
                'year' => $this->selectedYear,
                'month' => $monthKey,
                'model' => $modelName,
            ])->first();

            if (!$item) {
                throw new \Exception("Item not found");
            }

            // Update the status
            $item->status = $this->selectedStatus;
            $item->save();

            // Update the local data
            $this->purchaseData[$monthKey][$modelName]['status'] = $this->selectedStatus;

            Notification::make()
                ->title("Status updated to: {$this->selectedStatus}")
                ->success()
                ->send();

            $this->closeStatusModal();

        } catch (\Exception $e) {
            Log::error("Error updating status: " . $e->getMessage());

            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getDeviceModels()
    {
        return ShippingDeviceModel::where('is_active', true)
            ->orderBy('model_name')
            ->pluck('model_name')
            ->toArray();
    }

    // Save data from modal (both create and update)
    public function saveModalData()
    {
        try {
            $monthKey = $this->editingMonth;
            $modelName = $this->editingModel ?? $this->editingData['model'];

            // Validate model name for new entries
            if (!$this->editingModel && empty($modelName)) {
                Notification::make()
                    ->title('Please enter a model name')
                    ->warning()
                    ->send();
                return;
            }

            // Check if model already exists when creating new
            if (!$this->editingModel && isset($this->purchaseData[$monthKey][$modelName])) {
                Notification::make()
                    ->title('Model already exists for this month')
                    ->warning()
                    ->send();
                return;
            }

            // Find or create the purchase item
            $item = DevicePurchaseItem::firstOrNew([
                'year' => $this->selectedYear,
                'month' => $monthKey,
                'model' => $modelName,
            ]);

            // Update the fields
            $item->fill([
                'qty' => $this->editingData['qty'] ?? 0,
                'england' => $this->editingData['england'] ?? 0,
                'america' => $this->editingData['america'] ?? 0,
                'europe' => $this->editingData['europe'] ?? 0,
                'australia' => $this->editingData['australia'] ?? 0,
                'sn_no_from' => $this->editingData['sn_no_from'] ?? '',
                'sn_no_to' => $this->editingData['sn_no_to'] ?? '',
                'po_no' => $this->editingData['po_no'] ?? '',
                'order_no' => $this->editingData['order_no'] ?? '',
                'balance_not_order' => $this->editingData['balance_not_order'] ?? 0,
                'rfid_card_foc' => $this->editingData['rfid_card_foc'] ?? 0,
                'languages' => $this->editingData['languages'] ?? '',
                'features' => $this->editingData['features'] ?? '',
                'status' => $this->editingData['status'] ?? null,
            ]);

            // Save the item
            $item->save();

            Notification::make()
                ->title($this->editingModel ? 'Data updated successfully' : 'Model added successfully')
                ->success()
                ->send();

            // Close modal and refresh data
            $this->closeModal();
            $this->loadPurchaseData();

        } catch (\Exception $e) {
            Log::error("Error saving purchase item: " . $e->getMessage());

            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteModel($monthKey, $modelName)
    {
        try {
            DevicePurchaseItem::where('year', $this->selectedYear)
                ->where('month', $monthKey)
                ->where('model', $modelName)
                ->delete();

            unset($this->purchaseData[$monthKey][$modelName]);

            Notification::make()
                ->title("Model {$modelName} deleted successfully")
                ->success()
                ->send();

            $this->loadPurchaseData();

        } catch (\Exception $e) {
            Log::error("Error deleting model: " . $e->getMessage());

            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function loadPurchaseData()
    {
        $year = $this->selectedYear;
        $this->purchaseData = [];

        // Define months
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        // Get all purchase items for the selected year
        $purchaseItems = DevicePurchaseItem::where('year', $year)->get();

        // Initialize data structure
        foreach ($months as $monthNum => $monthName) {
            $this->purchaseData[$monthNum] = [];

            // Group items by model for this month
            $monthItems = $purchaseItems->where('month', $monthNum);

            foreach ($monthItems as $item) {
                $this->purchaseData[$monthNum][$item->model] = [
                    'qty' => $item->qty,
                    'england' => $item->england,
                    'america' => $item->america,
                    'europe' => $item->europe,
                    'australia' => $item->australia,
                    'sn_no_from' => $item->sn_no_from,
                    'sn_no_to' => $item->sn_no_to,
                    'po_no' => $item->po_no,
                    'order_no' => $item->order_no,
                    'balance_not_order' => $item->balance_not_order,
                    'rfid_card_foc' => $item->rfid_card_foc,
                    'languages' => $item->languages,
                    'features' => $item->features,
                    'status' => $item->status,
                ];
            }
        }

        // Prepare months with summary data
        $this->months = [];

        foreach ($months as $monthNum => $monthName) {
            $monthTotal = [
                'qty' => 0,
                'rfid_card_foc' => 0
            ];

            foreach ($this->purchaseData[$monthNum] as $model => $data) {
                $monthTotal['qty'] += $data['qty'];
                $monthTotal['rfid_card_foc'] += $data['rfid_card_foc'];
            }

            $this->months[$monthNum] = [
                'name' => $monthName,
                'num' => $monthNum,
                'totals' => $monthTotal,
                'models' => array_keys($this->purchaseData[$monthNum]),
            ];
        }
    }
}
