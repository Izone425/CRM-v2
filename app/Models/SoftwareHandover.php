<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoftwareHandover extends Model
{
    use HasFactory;

    protected $table = 'software_handovers';

    protected $fillable = [
        'lead_id',
        'created_by',
        'status',
        'project_priority',
        'status_handover',
        'handover_pdf',

        // Section 1: Company Details
        'company_name',
        'headcount',
        'category',  // Company size category
        'pic_name',
        'pic_phone',
        'salesperson',
        'payroll_code',
        'speaker_category',
        'admin_remarks_license',
        'admin_remarks_kickoff',

        // Section 2: Implementation Timeline
        'db_creation',
        'kick_off_meeting',
        'webinar_training',
        'go_live_date',
        'total_days',

        'ta',
        'tl',
        'tc',
        'tp',
        'tapp',
        'thire',
        'tacc',
        'tpbi',

        // Section 4: Implementation PICs
        'implementation_pics',
        'implementer',

        // Section 5: Training
        'training_type',

        // Section 6: Onsite Package
        'onsite_kick_off_meeting',
        'onsite_webinar_training',
        'onsite_briefing',

        // Section 7: Proforma Invoices
        'proforma_invoice_product',
        'proforma_invoice_hrdf',

        // Section 8: Attachments
        'confirmation_order_file',
        'payment_slip_file',
        'hrdf_grant_file',
        'invoice_file',
        'new_attachment_file',
        'license_activated',
        'data_migrated',
        'license_certification_id',

        // Section 9: Status & Remarks
        'reject_reason',
        'inactive_reason',
        'remarks',
        'submitted_at',
        'completed_at',

        'manual_follow_up_count',
        'follow_up_date',
        'follow_up_counter',
    ];

    protected $casts = [
        // Dates
        'db_creation' => 'date',
        'kick_off_meeting' => 'date',
        'webinar_training' => 'date',
        'go_live_date' => 'date',
        'submitted_at' => 'datetime',

        'ta' => 'boolean',
        'tl' => 'boolean',
        'tc' => 'boolean',
        'tp' => 'boolean',
        'tapp' => 'boolean',
        'thire' => 'boolean',
        'tacc' => 'boolean',
        'tpbi' => 'boolean',

        'onsite_kick_off_meeting' => 'boolean',
        'onsite_webinar_training' => 'boolean',
        'onsite_briefing' => 'boolean',

        'modules' => 'array',  // This ensures proper JSON handling
        'confirmation_order_file' => 'array',
        'payment_slip_file' => 'array',
        'hrdf_grant_file' => 'array',
        'invoice_file' => 'array',
        'new_attachment_file' => 'array',
        'implementation_pics' => 'array',
        'remarks' => 'array',
    ];

    /**
     * Get the total days since completion.
     *
     * @return int|string
     */
    public function getTotalDaysAttribute()
    {
        if (!$this->completed_at) {
            return 'N/A';
        }

        $completedDate = Carbon::parse($this->completed_at);
        $today = Carbon::now();
        return $completedDate->diffInDays($today);
    }

    /**
     * Set the remarks attribute to uppercase.
     *
     * @param mixed $value
     * @return void
     */
    public function setRemarksAttribute($value)
    {
        if (is_array($value)) {
            // If it's an array, uppercase each element's content
            foreach ($value as $key => $item) {
                if (isset($item['remark']) && is_string($item['remark'])) {
                    $value[$key]['remark'] = strtoupper($item['remark']);
                }
            }
            $this->attributes['remarks'] = json_encode($value);
        } else if (is_string($value)) {
            // If it's already JSON string
            if ($this->isJson($value)) {
                $decodedValue = json_decode($value, true);
                foreach ($decodedValue as $key => $item) {
                    if (isset($item['remark']) && is_string($item['remark'])) {
                        $decodedValue[$key]['remark'] = strtoupper($item['remark']);
                    }
                }
                $this->attributes['remarks'] = json_encode($decodedValue);
            } else {
                // If it's a plain string, just uppercase it
                $this->attributes['remarks'] = strtoupper($value);
            }
        } else {
            // Otherwise, just set it as is
            $this->attributes['remarks'] = $value;
        }
    }

    /**
     * Set the reject_reason attribute to uppercase.
     *
     * @param string|null $value
     * @return void
     */
    public function setRejectReasonAttribute($value)
    {
        $this->attributes['reject_reason'] = is_string($value) ? strtoupper($value) : $value;
    }

    /**
     * Set the payroll_code attribute to uppercase.
     *
     * @param string|null $value
     * @return void
     */
    public function setPayrollCodeAttribute($value)
    {
        $this->attributes['payroll_code'] = is_string($value) ? strtoupper($value) : $value;
    }

    /**
     * Check if a string is valid JSON
     *
     * @param string $string
     * @return bool
     */
    private function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }

        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Get the purchase type options
     *
     * @return array
     */
    public static function getPurchaseTypeOptions(): array
    {
        return ['Purchase', 'Free'];
    }

    /**
     * Get the lead that owns this software handover
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user who created this handover
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function implementerAppointments()
    {
        return $this->hasMany(ImplementerAppointment::class, 'software_handover_id');
    }

    public function getCompanySizeLabelAttribute()
    {
        switch ($this->company_size) {
            case '1-24':
                return 'Small';
            case '25-99':
                return 'Medium';
            case '100-500':
                return 'Large';
            case '501 and Above':
                return 'Enterprise';
            default:
                return 'Unknown'; // fallback if `company_size` is an unexpected value
        }
    }
}
