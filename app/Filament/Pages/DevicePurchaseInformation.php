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
    public $selectedStatuses = ['All'];

    public $filterYear = '';
    public $filterMonth = '';
    public $filterStatus = '';
    public $filterModel = '';

    public $showFilters = false;

    public function clearAllFilters()
    {
        $this->selectedStatuses = ['All'];
        $this->filterYear = '';
        $this->filterMonth = '';
        $this->filterStatus = '';
        $this->filterModel = '';

        if ($this->isRawView) {
            $this->loadRawData();
        } else {
            $this->loadPurchaseData();
        }
    }

    public function mount()
    {
        $this->selectedYear = request()->query('year', Carbon::now()->year);
        $this->showFilters = false; // Initialize as collapsed

        // Initialize filters
        $this->filterYear = $this->selectedYear;
        $this->filterMonth = '';
        $this->filterStatus = '';
        $this->filterModel = '';

        // Handle multiple statuses from query parameters
        $statusParam = request()->query('status', 'All');
        if (is_array($statusParam)) {
            $this->selectedStatuses = $statusParam;
        } else {
            $this->selectedStatuses = $statusParam === 'All' ? ['All'] : [$statusParam];
        }

        $this->loadPurchaseData();
        $currentMonth = (int)date('n');
        $this->selectedMonth = $currentMonth;

        if ($this->isRawView) {
            $this->loadRawData();
        }
    }

    private function canModify(): bool
    {
        return auth()->user()?->role_id == 3;
    }

    public function updateRawDataFilters()
    {
        $this->loadRawData();
    }

    public function clearRawDataFilters()
    {
        $this->filterYear = '';
        $this->filterMonth = '';
        $this->filterStatus = '';
        $this->filterModel = '';
        $this->loadRawData();
    }

    public function getAvailableYears()
    {
        return DevicePurchaseItem::distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
    }

    public function getAvailableModels()
    {
        // Get existing models from database
        $dbModels = DevicePurchaseItem::distinct()
            ->orderBy('model')
            ->pluck('model')
            ->toArray();

        // Define standard device models
        $standardModels = [
            'TC10',
            'TC20',
            'FACE ID5',
            'FACE ID6',
            'NFC Tag',
            'Time Beacon'
        ];

        // Merge and remove duplicates, then sort
        $allModels = array_unique(array_merge($standardModels, $dbModels));
        sort($allModels);

        return $allModels;
    }

    public function selectMonth($monthNum)
    {
        $this->selectedMonth = $monthNum;
    }

    // Define available status options
    public function getStatusOptions(): array
    {
        return [
            'All' => 'All Statuses',
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
                ->url(fn() => route('filament.admin.pages.device-purchase-information', [
                    'year' => $year,
                    'status' => $this->selectedStatuses
                ]))
                ->color($year == $this->selectedYear ? 'primary' : 'warning');
        }

        $actions[] = Action::make('toggle_view')
            ->label(fn() => $this->isRawView ? 'Switch to Process View' : 'Switch to Raw View')
            ->color('gray')
            ->action(function () {
                $this->toggleViewMode();
            });

        return $actions;
    }

    // Update status filter and reload data
    public function updateStatusFilter($statuses)
    {
        $this->selectedStatuses = $statuses;
        $this->loadPurchaseData();

        if ($this->isRawView) {
            $this->loadRawData();
        }
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
        if (!$this->canModify()) {
            Notification::make()
                ->title('Access Denied')
                ->body('You do not have permission to edit device purchase information.')
                ->danger()
                ->send();
            return;
        }

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
        if (!$this->canModify()) {
            Notification::make()
                ->title('Access Denied')
                ->body('You do not have permission to add new device purchase information.')
                ->danger()
                ->send();
            return;
        }

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
            'date_completed_order' => now(),
            'date_completed_shipping' => '',
            'date_completed_delivery' => '',
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
        if (!$this->canModify()) {
            Notification::make()
                ->title('Access Denied')
                ->body('You do not have permission to update status.')
                ->danger()
                ->send();
            return;
        }

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
        if (!$this->canModify()) {
            Notification::make()
                ->title('Access Denied')
                ->body('You do not have permission to update status.')
                ->danger()
                ->send();
            return;
        }

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

            // Update the status and corresponding date
            $item->status = $this->updatingStatus;

            // Automatically set the completion date based on status
            switch ($this->updatingStatus) {
                case 'Completed Order':
                    $item->date_completed_order = now()->toDateString();
                    break;
                case 'Completed Shipping':
                    $item->date_completed_shipping = now()->toDateString();
                    break;
                case 'Completed Delivery':
                    $item->date_completed_delivery = now()->toDateString();
                    break;
            }

            $item->save();

            // Update the local data if not in raw view
            if (!$this->isRawView) {
                $this->purchaseData[$monthKey][$uniqueKey]['status'] = $this->updatingStatus;
                $this->purchaseData[$monthKey][$uniqueKey]['date_completed_order'] = $item->date_completed_order;
                $this->purchaseData[$monthKey][$uniqueKey]['date_completed_shipping'] = $item->date_completed_shipping;
                $this->purchaseData[$monthKey][$uniqueKey]['date_completed_delivery'] = $item->date_completed_delivery;
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
        if (!$this->canModify()) {
            Notification::make()
                ->title('Access Denied')
                ->body('You do not have permission to save changes.')
                ->danger()
                ->send();
            return;
        }

        try {
            // Validation
            $errors = [];

            if (empty($this->editingData['model'])) {
                $errors[] = 'Model name is required';
            }

            if (empty($this->editingData['qty']) || $this->editingData['qty'] < 1) {
                $errors[] = 'Quantity is required and must be at least 1';
            }

            if (empty($this->editingData['languages'])) {
                $errors[] = 'Languages field is required';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    Notification::make()
                        ->title($error)
                        ->warning()
                        ->send();
                }
                return;
            }

            $monthKey = $this->editingMonth;

            // Check if we're editing an existing record or creating a new one
            if ($this->editingModel) {
                // We're editing an existing record
                $parts = explode('_', $this->editingModel);
                $itemId = end($parts);

                $item = DevicePurchaseItem::find($itemId);

                if (!$item) {
                    throw new \Exception("Item not found");
                }

                // Check if model name is being changed
                $oldModel = $item->model;
                $newModel = $this->editingData['model'];

                // Update the model name
                $item->model = $newModel;
            } else {
                // We're creating a new record
                $modelName = $this->editingData['model'];

                // Check if model already exists for this year/month combination
                $existingItem = DevicePurchaseItem::where('year', $this->selectedYear)
                    ->where('month', $monthKey)
                    ->where('model', $modelName)
                    ->first();

                if ($existingItem) {
                    Notification::make()
                        ->title("Model '{$modelName}' already exists for this month. Please choose a different model name or edit the existing entry.")
                        ->warning()
                        ->send();
                    return;
                }

                $item = new DevicePurchaseItem();
                $item->year = $this->selectedYear;
                $item->month = $monthKey;
                $item->model = $modelName;

                $uniqueId = $this->selectedYear . '_' . $monthKey . '_' . $modelName . '_' . uniqid();
                $item->device_purchase_items_year_month_model_unique = $uniqueId;
            }

            // Convert specific fields to uppercase
            $languages = strtoupper($this->editingData['languages'] ?? '');
            $po_no = strtoupper($this->editingData['po_no'] ?? '');
            $order_no = strtoupper($this->editingData['order_no'] ?? '');

            // Update the remaining fields
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

            // Update date fields
            $item->date_completed_order = $this->editingData['date_completed_order'] ?: null;
            $item->date_completed_shipping = $this->editingData['date_completed_shipping'] ?: null;
            $item->date_completed_delivery = $this->editingData['date_completed_delivery'] ?: null;

            $item->save();

            Notification::make()
                ->title($this->editingModel ? 'Data updated successfully' : 'Model added successfully')
                ->success()
                ->send();

            $this->closeModal();
            $this->loadPurchaseData();

            // Reload raw data if in raw view
            if ($this->isRawView) {
                $this->loadRawData();
            }

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
        if (!$this->canModify()) {
            Notification::make()
                ->title('Access Denied')
                ->body('You do not have permission to delete records.')
                ->danger()
                ->send();
            return;
        }

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
        $query = DevicePurchaseItem::query();

        // Apply filters
        if ($this->filterYear) {
            $query->where('year', $this->filterYear);
        }

        if ($this->filterMonth) {
            $query->where('month', $this->filterMonth);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterModel) {
            $query->where('model', $this->filterModel);
        }

        // Apply status filter from main filter if no specific status filter
        if (!$this->filterStatus && !in_array('All', $this->selectedStatuses)) {
            $query->whereIn('status', $this->selectedStatuses);
        }

        // Sort by year DESC, month DESC
        $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('created_at', 'desc');

        $this->rawData = $query->get()->map(function ($item) {
            // Get the most recent status date
            $statusDate = null;
            switch ($item->status) {
                case 'Completed Order':
                    $statusDate = $item->date_completed_order;
                    break;
                case 'Completed Shipping':
                    $statusDate = $item->date_completed_shipping;
                    break;
                case 'Completed Delivery':
                    $statusDate = $item->date_completed_delivery;
                    break;
            }

            return [
                'id' => $item->id,
                'year' => $item->year,
                'month' => $item->month,
                'month_name' => date('F', mktime(0, 0, 0, $item->month, 1)),
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
                'status_date' => $statusDate,
                'date_completed_order' => $item->date_completed_order,
                'date_completed_shipping' => $item->date_completed_shipping,
                'date_completed_delivery' => $item->date_completed_delivery,
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

        if (!in_array('All', $this->selectedStatuses)) {
            $query->whereIn('status', $this->selectedStatuses);
        }

        $purchaseItems = $query->get();

        // Initialize data structure
        foreach ($months as $monthNum => $monthName) {
            $this->purchaseData[$monthNum] = [];

            $monthItems = $purchaseItems->where('month', $monthNum);

            foreach ($monthItems as $item) {
                $uniqueKey = $item->model . '_' . $item->id;

                $this->purchaseData[$monthNum][$uniqueKey] = [
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
                    'date_completed_order' => $item->date_completed_order,
                    'date_completed_shipping' => $item->date_completed_shipping,
                    'date_completed_delivery' => $item->date_completed_delivery,
                    'id' => $item->id,
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
