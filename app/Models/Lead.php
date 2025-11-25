<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class Lead extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'id',
        'zoho_id',
        'name',
        'email',
        'phone',
        'company_name',
        'company_size',
        'country',
        'products',
        'lead_code',
        'categories',
        'stage',
        'lead_status',
        'hot_percentage',
        'lead_owner',
        'salesperson',
        'follow_up_date',
        'closing_date',
        'pickup_date',
        'remark',
        'demo_appointment',
        'customer_type',
        'region',
        'follow_up_needed',
        'follow_up_counter',
        'follow_up_count',
        'manual_follow_up_count',
        'rfq_followup_at',
        'rfq_transfer_at',
        'call_attempt',
        'done_call',
        'salesperson_assigned_date',
        'deal_amount',
        'contact_id',
        'reseller_id',
        'visible_in_repairs',
        'created_at',
        'updated_at',
        'closed_by',
        'meta_lead_id',
        'meta_event_sent_at',
        'fbclid',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'id',
                'zoho_id',
                'name',
                'email',
                'phone',
                'company_name',
                'company_size',
                'country',
                'products',
                'lead_code',
                'categories',
                'stage',
                'lead_status',
                'lead_owner',
                'salesperson',
                'follow_up_date',
                'remark',
                'demo_appointment',
                'customer_type',
                'region',
                'follow_up_needed',
                'follow_up_counter',
                'pickup_date',
                'follow_up_count',
                'manual_follow_up_count',
                'rfq_followup_at',
                'call_attempt',
                'done_call',
                'salesperson_assigned_date',
                'closing_date',
                'deal_amount',
                'contact_id',
                'reseller_id',
                'created_at',
                'updated_at'
            ]);
    }

    protected $casts = [
        'follow_up_date' => 'date:Y-m-d',
        'rfq_followup_at' => 'datetime',
        'products' => 'array',
    ];

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtoupper($value);
    }

    public function setRemarkAttribute($value)
    {
        $this->attributes['remark'] = strtoupper($value);
    }

    public static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if (
                ($model->isDirty('lead_status') && $model->lead_status === 'RFQ-Follow Up')
            ) {
                $model->rfq_followup_at = now();
            }
            // dd($model->lead_status);
            // If lead_status changes to Demo Cancelled, reset the rfq_followup_at timestamp
            if ($model->isDirty('lead_status') && $model->lead_status === 'Demo Cancelled') {
                $model->rfq_followup_at = null;
            }
        });
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

    public function calculateDaysFromNewDemo()
    {
        // Get the earliest demo appointment for this lead
        $firstAppointment = $this->demoAppointment()->orderBy('created_at', 'asc')->first();

        if (!$firstAppointment) {
            return '-'; // No appointments linked
        }

        return $firstAppointment->created_at->diffInDays(now());
    }

    public function calculateDaysFromRFQTransferToInactive()
    {
        // If RFQ-Transfer date is not set, return '-'
        if (!$this->rfq_followup_at) {
            return '-';
        }

        // If categories is 'Inactive', calculate from rfq_followup_at to updated_at
        if ($this->category === 'Inactive') {
            return $this->rfq_followup_at->diffInDays($this->updated_at);
        }

        // Otherwise, calculate from rfq_followup_at to now
        return $this->rfq_followup_at->diffInDays(now());
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'subject_id');
    }

    public function implementerLogs(): HasMany
    {
        return $this->hasMany(ImplementerLogs::class, 'lead_id', 'id');
    }

    public function adminRenewalLogs(): HasMany
    {
        return $this->hasMany(AdminRenewalLogs::class, 'lead_id', 'id');
    }

    public function demoAppointment(): HasMany
    {
        return $this->hasMany(Appointment::class, 'lead_id', 'id');
    }

    public function repairAppointment(): HasMany
    {
        return $this->hasMany(RepairAppointment::class, 'lead_id', 'id');
    }

    public function implementerAppointment(): HasMany
    {
        return $this->hasMany(ImplementerAppointment::class, 'lead_id', 'id');
    }

    public function repairHandover(): HasMany
    {
        return $this->hasMany(AdminRepair::class, 'lead_id', 'id');
    }

    public function implementerNotes()
    {
        return $this->hasMany(ImplementerNote::class);
    }

    public function implementerForms()
    {
        return $this->hasMany(ImplementerForm::class);
    }

    public function systemQuestion(): HasOne
    {
        return $this->hasOne(SystemQuestion::class, 'lead_id', 'id');
    }

    public function systemQuestionPhase2(): HasOne
    {
        return $this->hasOne(SystemQuestionPhase2::class, 'lead_id', 'id');
    }

    public function systemQuestionPhase3(): HasOne
    {
        return $this->hasOne(SystemQuestionPhase3::class, 'lead_id', 'id');
    }

    public function bankDetail(): HasOne
    {
        return $this->hasOne(BankDetail::class, 'lead_id', 'id');
    }

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_code', 'lead_code');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function referralDetail(): HasOne
    {
        return $this->hasOne(ReferralDetail::class);
    }

    public function companyDetail(): HasOne
    {
        return $this->hasOne(CompanyDetail::class);
    }

    public function utmDetail(): HasOne
    {
        return $this->hasOne(UtmDetail::class);
    }

    public function eInvoiceDetail()
    {
        return $this->hasOne(EInvoiceDetail::class);
    }

    public function softwareHandover()
    {
        return $this->hasMany(SoftwareHandover::class);
    }

    public function hardwareHandover()
    {
        return $this->hasMany(HardwareHandover::class);
    }

    public function hardwareHandoverV2()
    {
        return $this->hasMany(HardwareHandoverV2::class);
    }

    public function hrdfHandover()
    {
        return $this->hasMany(HRDFHandover::class);
    }


    public function headcountHandover()
    {
        return $this->hasMany(HeadcountHandover::class);
    }

    public function financeHandover()
    {
        return $this->hasMany(FinanceHandover::class);
    }

    public function reseller(): HasOne
    {
        return $this->hasOne(Reseller::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(Renewal::class);
    }

    public function renewalNotes()
    {
        return $this->hasMany(RenewalNote::class);
    }


    public function subsidiaries()
    {
        return $this->hasMany(Subsidiary::class);
    }

    public function callLogs()
    {
        return $this->hasMany(CallLog::class);
    }

    public function salespersonUser()
    {
        return $this->belongsTo(User::class, 'salesperson');
    }

    public function getSalespersonUser()
    {
        if (!$this->salesperson) {
            return null;
        }

        // If salesperson is stored as user ID
        if (is_numeric($this->salesperson)) {
            return User::find($this->salesperson);
        }

        // If salesperson is stored as name, search by name
        return User::where('name', $this->salesperson)->first();
    }

    public function getSalespersonEmail()
    {
        $user = $this->getSalespersonUser();
        return $user ? $user->email : null;
    }

    // You can also add this as an accessor for easier access
    public function getSalespersonEmailAttribute()
    {
        return $this->getSalespersonEmail();
    }

    // public function getDealAmountAttribute()
    // {
    //     return $this->quotations->sum(function ($quotation) {
    //         return $quotation->items->sum('total_after_tax');
    //     });
    // }

    protected static $productMapping = [
        'smart_parking' => 'Smart Parking Management (Cashless, LPR, Valet)',
        'hr' => 'HR (Attendance, Leave, Claim, Payroll, Hire, Profile)',
        'property_management' => 'Property Management (Neighbour, Accounting)',
        'security_people_flow' => 'Security & People Flow (Visitor, Access, Patrol, IoT)',
        'merchants' => 'i-Merchants (Near Field Commerce, Loyalty Program)',
        'smart_city' => 'Smart City',
    ];

    // Accessor for formatted products
    public function getFormattedProductsAttribute()
    {
        $products = is_string($this->products) ? json_decode($this->products, true) : $this->products;

        return collect($products)
            ->map(fn($product) => self::$productMapping[$product] ?? ucwords(str_replace('_', ' ', $product)))
            ->join(', ');
    }

    public function projectPlans(): HasMany
    {
        return $this->hasMany(ProjectPlan::class);
    }
}
