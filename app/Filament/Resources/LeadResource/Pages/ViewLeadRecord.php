<?php
namespace App\Filament\Resources\LeadResource\Pages;

use App\Classes\Encryptor;
use App\Filament\Resources\LeadResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Support\HtmlString;
use App\Enums\LeadCategoriesEnum;
use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use App\Filament\Resources\LeadResource\Pages;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\QuoteResource\Pages\CreateQuote;
use App\Filament\Resources\LeadResource\RelationManagers;
use App\Models\Lead;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use App\Filament\Resources\LeadResource\RelationManagers\ActivityLogRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\DemoAppointmentRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\LeadDetailRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\LeadSourceRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\ProformaInvoiceRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\QuotationRelationManager;
use App\Models\ActivityLog;
use Filament\Resources\RelationManagers\RelationManager;
use App\Models\LeadSource;
use App\Models\SystemQuestion;
use Carbon\Carbon;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section as ComponentsSection;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions\Action;

class ViewLeadRecord extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    public function mount($record): void
    {
            $code = str_replace(' ', '+', $record); // Replace spaces with +
            $leadId = Encryptor::decrypt($code); // Decrypt the encrypted record ID
            // dd($leadId);
            $this->record = $this->getModel()::findOrFail($leadId); // Fetch the lead record
    }

    public function getTitle(): HtmlString
    {
        $companyName = $this->record->companyDetail->company_name ?? 'Lead Details';
        $leadStatus = $this->record->lead_status ?? 'Unknown';

        // Define background color for lead_status
        $statusColor = match ($leadStatus) {
            'None' => '#ffe1a5',
            'New' => '#ffe1a5',
            'RFQ-Transfer' => '#ffe1a5',
            'Pending Demo' => '#ffe1a5',
            'Under Review' => '#ffe1a5',
            'Demo Cancelled' => '#ffe1a5',
            'Demo-Assigned' => '#ffffa5',
            'RFQ-Follow Up' => '#431fa1e3',
            'Hot' => '#ff0000a1',
            'Warm' => '#FFA500',
            'Cold' => '#00e7ff',
            'Junk' => '#E5E4E2',
            'On Hold' => '#E5E4E2',
            'Lost' => '#E5E4E2',
            'No Response' => '#E5E4E2',
            'Closed' => '#E5E4E2',
            default => '#cccccc',
        };

        // Return the HTML string
        return new HtmlString(
            sprintf(
                '<div style="display: flex; align-items: center; gap: 10px;">
                    <h1 style="margin: 0; font-size: 1.5rem;">%s</h1>
                    <span style="background-color: %s; text-align: -webkit-center; width:160px; border-radius: 25px; font-size: 1.25rem;">
                        %s
                    </span>
                </div>',
                e($companyName),  // Escaped company name
                $statusColor,     // Dynamic background color
                e($leadStatus)    // Escaped lead status
            )
        );
    }
}
