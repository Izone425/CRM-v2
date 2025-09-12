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
    public $selectedStatus = 'All'; // Add status filter default value
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

    public $selectedMonth = null;

    public $isRawView = false;
    public $rawData = [];

    public $updatingStatus = null;

    public function mount()
    {
        $this->selectedYear = request()->query('year', Carbon::now()->year);
        $this->selectedStatus = request()->query('status', 'All');
        $this->loadPurchaseData();
        $currentMonth = (int)date('n');

        // Select the current month by default
        $this->selectedMonth = $currentMonth;

        // Load raw data if raw view is enabled
        if ($this->isRawView) {
            $this->loadRawData();
        }
    }

    public function selectMonth($monthNum)
    {
        $this->selectedMonth = $monthNum;
    }

    // Define available status options
    public function getStatusOptions(): array
    {
        return [
            'All' => 'All',
            'Completed Order' => 'Completed Order',
            'Completed Shipping' => 'Completed Shipping',
            'Completed Delivery' => 'Completed Delivery',
        ];
    }

    protected function getHeaderActions(): array
    {
        $years = range(Carbon::now()->year, Carbon::now()->year + 2);

        $actions = [];
        foreach ($years as $year) {
            $actions[] = Action::make("year_$year")
                ->label($year)
                ->url(fn() => route('filament.admin.pages.device-purchase-information', ['year' => $year, 'status' => $this->selectedStatus]))
                ->color($year == $this->selectedYear ? 'primary' : 'warning');
        }

        $actions[] = Action::make('toggle_view')
            ->label(fn() => $this->isRawView ? 'Switch to Process View' : 'Switch to Raw View')
            // ->icon(fn() => $this->isRawView ? 'heroicon-o-view-columns' : 'heroicon-o-table')
            ->color('gray')
            ->action(function () {
                $this->toggleViewMode();
            });

        return $actions;
    }

    // Update status filter and reload data
    public function updateStatusFilter($status)
    {
        $this->selectedStatus = $status;
        $this->loadPurchaseData();
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
    public function openEditModal($monthKey, $uniqueKey)
    {
        $this->editingMonth = $monthKey;
        $this->editingModel = $uniqueKey;

        if ($this->isRawView) {
            // In raw view, find the item by ID
            $parts = explode('_', $uniqueKey);
            $itemId = end($parts);

            foreach ($this->rawData as $item) {
                if ($item['id'] == $itemId) {
                    $this->editingData = $item;
                    break;
                }
            }
        } else {
            // In process view, use the existing code
            $this->editingData = $this->purchaseData[$monthKey][$uniqueKey];
        }

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
    public function openStatusModal($monthKey, $uniqueKey)
    {
        $this->statusMonth = $monthKey;
        $this->statusModel = $uniqueKey;

        if ($this->isRawView) {
            // In raw view, find the item by ID
            $parts = explode('_', $uniqueKey);
            $itemId = end($parts);

            foreach ($this->rawData as $item) {
                if ($item['id'] == $itemId) {
                    $this->updatingStatus = $item['status'] ?? null;
                    break;
                }
            }
        } else {
            // In process view, use the existing code
            $this->updatingStatus = $this->purchaseData[$monthKey][$uniqueKey]['status'] ?? null;
        }

        $this->isStatusModalOpen = true;
    }

    // Modify the closeStatusModal method
    public function closeStatusModal()
    {
        $this->isStatusModalOpen = false;
        $this->statusMonth = null;
        $this->statusModel = null;
        $this->updatingStatus = null; // Reset the updating status
    }

    // Update the updateStatus method to use updatingStatus instead of selectedStatus
    public function updateStatus()
    {
        try {
            $monthKey = $this->statusMonth;
            $uniqueKey = $this->statusModel;

            // Get the ID
            if ($this->isRawView) {
                $parts = explode('_', $uniqueKey);
                $itemId = end($parts);
            } else {
                $itemId = $this->purchaseData[$monthKey][$uniqueKey]['id'];
            }

            // Find the purchase item by ID
            $item = DevicePurchaseItem::find($itemId);

            if (!$item) {
                throw new \Exception("Item not found");
            }

            // Update the status
            $item->status = $this->updatingStatus;
            $item->save();

            // Update the local data if not in raw view
            if (!$this->isRawView) {
                $this->purchaseData[$monthKey][$uniqueKey]['status'] = $this->updatingStatus;
            }

            Notification::make()
                ->title("Status updated to: {$this->updatingStatus}")
                ->success()
                ->send();

            $this->closeStatusModal();
            $this->loadPurchaseData(); // Reload data to apply filters

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
            ->orderBy('created_at', 'asc')
            ->pluck('model_name')
            ->toArray();
    }

    // Save data from modal (both create and update)
    public function saveModalData()
    {
        try {
            $monthKey = $this->editingMonth;

            // Check if we're editing an existing record or creating a new one
            if ($this->editingModel) {
                // We're editing an existing record
                // Extract the model ID from the uniqueKey
                $parts = explode('_', $this->editingModel);
                $itemId = end($parts); // Get the last part which should be the ID

                // Find the existing record
                $item = DevicePurchaseItem::find($itemId);

                if (!$item) {
                    throw new \Exception("Item not found");
                }
            } else {
                // We're creating a new record
                $modelName = $this->editingData['model'];

                // Validate model name for new entries
                if (empty($modelName)) {
                    Notification::make()
                        ->title('Please enter a model name')
                        ->warning()
                        ->send();
                    return;
                }

                // Create a new item
                $item = new DevicePurchaseItem();
                $item->year = $this->selectedYear;
                $item->month = $monthKey;
                $item->model = $modelName;

                // Generate a unique identifier to prevent duplicate key errors
                $uniqueId = $this->selectedYear . '_' . $monthKey . '_' . $modelName . '_' . uniqid();
                $item->device_purchase_items_year_month_model_unique = $uniqueId;
            }

            // Convert specific fields to uppercase
            $languages = strtoupper($this->editingData['languages'] ?? '');
            $po_no = strtoupper($this->editingData['po_no'] ?? '');
            $order_no = strtoupper($this->editingData['order_no'] ?? '');

            // Update the fields
            $item->qty = $this->editingData['qty'] ?? 0;
            $item->england = $this->editingData['england'] ?? 0;
            $item->america = $this->editingData['america'] ?? 0;
            $item->europe = $this->editingData['europe'] ?? 0;
            $item->australia = $this->editingData['australia'] ?? 0;
            $item->sn_no_from = $this->editingData['sn_no_from'] ?? '';
            $item->sn_no_to = $this->editingData['sn_no_to'] ?? '';
            $item->po_no = $po_no;
            $item->order_no = $order_no;
            $item->balance_not_order = $this->editingData['balance_not_order'] ?? 0;
            $item->rfid_card_foc = $this->editingData['rfid_card_foc'] ?? 0;
            $item->languages = $languages;
            $item->features = $this->editingData['features'] ?? '';
            $item->status = $this->editingData['status'] ?? 'Completed Order';

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

    public function deleteModel($monthKey, $uniqueKey)
    {
        try {
            // Get the ID
            if ($this->isRawView) {
                $parts = explode('_', $uniqueKey);
                $itemId = end($parts);
            } else {
                $itemId = $this->purchaseData[$monthKey][$uniqueKey]['id'];
            }

            // Delete by ID
            DevicePurchaseItem::where('id', $itemId)->delete();

            if (!$this->isRawView) {
                unset($this->purchaseData[$monthKey][$uniqueKey]);
            }

            Notification::make()
                ->title("Model deleted successfully")
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

    public function toggleViewMode()
    {
        $this->isRawView = !$this->isRawView;

        // Load raw data if raw view is enabled
        if ($this->isRawView) {
            $this->loadRawData();
        }
    }

    public function loadRawData()
    {
        $year = $this->selectedYear;

        // Create a query for all purchase items for the selected year
        $query = DevicePurchaseItem::where('year', $year);

        // Apply status filter if not "All"
        if ($this->selectedStatus !== 'All') {
            $query->where('status', $this->selectedStatus);
        }

        // Get the data and convert it to an array
        $this->rawData = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'year' => $item->year,
                'month' => $item->month,
                'model' => $item->model,
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
        })->toArray();
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

        // Get all purchase items for the selected year with status filter
        $query = DevicePurchaseItem::where('year', $year);

        // Apply status filter if not "All"
        if ($this->selectedStatus !== 'All') {
            $query->where('status', $this->selectedStatus);
        }

        $purchaseItems = $query->get();

        // Initialize data structure
        foreach ($months as $monthNum => $monthName) {
            $this->purchaseData[$monthNum] = [];

            // Group items by model for this month
            $monthItems = $purchaseItems->where('month', $monthNum);

            foreach ($monthItems as $item) {
                // Use a unique identifier for each record (model + timestamp)
                $uniqueKey = $item->model . '_' . $item->id;

                $this->purchaseData[$monthNum][$uniqueKey] = [
                    'model' => $item->model, // Add model as a field so we can use it in the template
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
                    'id' => $item->id, // Store the ID for future operations
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

            foreach ($this->purchaseData[$monthNum] as $uniqueKey => $data) {
                $monthTotal['qty'] += $data['qty'];
                $monthTotal['rfid_card_foc'] += $data['rfid_card_foc'];
            }

            $this->months[$monthNum] = [
                'name' => $monthName,
                'num' => $monthNum,
                'totals' => $monthTotal,
                'models' => array_unique(array_column($this->purchaseData[$monthNum], 'model')),
            ];
        }

        if ($this->isRawView) {
            $this->loadRawData();
        }
    }
}
