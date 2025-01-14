<?php
namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\ActivityLog;
use App\Models\Lead;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ViewLead extends ListRecords
{
    protected static string $resource = LeadResource::class;
    protected static ?string $title = 'Lead Details';
    protected static ?string $model = ActivityLog::class;

    public $companySize;
    public $companyName;
    public $leads;
    public $updateDate;
    public $activities;
    public $leadStatus;
    public $leadStage;
    public $openModal = false;
    public $remark;
    public $nextFollowUp;

    public function mount(): void
    {
        parent::mount();

        // Retrieve the ID from the URL
        $record = request()->route('record');

        // Fetch the related activities for this lead
        $this->activities = ActivityLog::where('subject_id', $record)->orderBy('updated_at', 'desc')->get();
        // dd($this->activities->pluck('updated_at'));  // Dumps all updated_at values from the ActivityLog

        // Fetch the Demo record based on the ID from the URL
        $this->activities = ActivityLog::where('subject_id', $record)
        ->orderBy('updated_at', 'desc')
        ->get();
        // $activityLogs = ActivityLog::where('subject_id', $this->getRecordId())->get();
        // dd($activityLogs);
        $lead = Lead::find($record);
        $this->companyName = $lead->company_name;
        $this->companySize = $lead->company_size;
        $this->updateDate = $lead->updated_at;
        $this->leadStatus = $lead->lead_status;
        $this->leadStage = $lead->stage;
        $this->leads = Lead::all();
    }

    public function getView(): string
    {
        return 'filament.pages.view-lead-tabs';
    }

    protected function getRecordId()
    {
        return request()->route('record'); // Get the record ID from the URL
    }
}
