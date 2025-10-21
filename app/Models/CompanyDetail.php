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

class CompanyDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'company_name',
        'industry',
        'company_address1',
        'company_address2',
        'postcode',
        'reg_no_new',
        'reg_no_old',
        'state',
        'name',
        'contact_no',
        'position',
        'email',
        'additional_pic',
        'additional_prospect_pic',
        'website_url',
        'linkedin_url',
    ];

    public function setCompanyNameAttribute($value)
    {
        $this->attributes['company_name'] = strtoupper($value);
    }

    public function setIndustryAttribute($value)
    {
        $this->attributes['industry'] = strtoupper($value);
    }

    public function setCompanyAddress1Attribute($value)
    {
        $this->attributes['company_address1'] = strtoupper($value);
    }

    public function setCompanyAddress2Attribute($value)
    {
        $this->attributes['company_address2'] = strtoupper($value);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtoupper($value);
    }

    public function setPositionAttribute($value)
    {
        $this->attributes['position'] = strtoupper($value);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'id');
    }
}
