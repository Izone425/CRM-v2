<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Subsidiary extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'company_name',
        'company_address1',
        'company_address2',
        'register_number',
        'postcode',
        'state',
        'industry',
        'name',
        'contact_number',
        'email',
        'position',
        'created_at',
        'updated_at',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // Convert all attribute values to uppercase before saving, except email
    public function setAttribute($key, $value)
    {
        if (is_string($value) && $key !== 'email') {
            $value = Str::upper($value);
        }

        return parent::setAttribute($key, $value);
    }

    // Accessors to ensure uppercase retrieval
    public function getCompanyNameAttribute($value)
    {
        return Str::upper($value);
    }

    public function getCompanyAddress1Attribute($value)
    {
        return $value ? Str::upper($value) : null;
    }

    public function getCompanyAddress2Attribute($value)
    {
        return $value ? Str::upper($value) : null;
    }

    public function getRegisterNumberAttribute($value)
    {
        return $value ? Str::upper($value) : null;
    }

    public function getPostcodeAttribute($value)
    {
        return $value ? Str::upper($value) : null;
    }

    public function getIndustryAttribute($value)
    {
        return Str::upper($value);
    }

    public function getNameAttribute($value)
    {
        return Str::upper($value);
    }

    public function getPositionAttribute($value)
    {
        return Str::upper($value);
    }

    public function getContactNumberAttribute($value)
    {
        return Str::upper($value);
    }
}
