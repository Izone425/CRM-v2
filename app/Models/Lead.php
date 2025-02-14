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
        'follow_up_needed',
        'follow_up_counter',
        'follow_up_count',
        'rfq_followup_at',
        'rfq_transfer_at',
        'call_attempt',
        'done_call',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'id',
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
                'follow_up_needed',
                'follow_up_counter',
                'follow_up_count',
                'demo_follow_up_count',
                'rfq_followup_at',
                'call_attempt',
                'done_call',
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
        // Get the related demo appointment
        $appointment = $this->demoAppointment()->first(); // Assuming a single appointment is linked

        if (!$appointment) {
            return '-'; // No appointment linked
        }

        // Check the status of the appointment and calculate accordingly
        if ($appointment->status === 'New') {
            return $appointment->created_at->diffInDays(now());
        } elseif ($appointment->status === 'Done') {
            return $appointment->created_at->diffInDays($appointment->updated_at);
        } elseif ($appointment->status === 'Cancelled') {
            return '0'; // For cancelled appointments
        }

        return '-'; // Default case
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

    public function demoAppointment(): HasMany
    {
        return $this->hasMany(Appointment::class, 'lead_id', 'id');
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

    public function getDealAmountAttribute()
    {
        return $this->quotations->sum(function ($quotation) {
            return $quotation->items->sum('total_after_tax');
        });
    }

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

    // public function getCurrencyAttribute()
    // {
    //     return $this->quotations->currency;
    // }
}
