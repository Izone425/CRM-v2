<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EInvoiceDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'e_invoice_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'lead_id',
        'pic_email',
        'tin_no',
        'new_business_reg_no',
        'old_business_reg_no',
        'registration_name',
        'identity_type',
        'tax_classification',
        'sst_reg_no',
        'msic_code',
        'msic_code_2',
        'msic_code_3',
        'business_address',
        'postcode',
        'contact_number',
        'email_address',
        'city',
        'country',
        'state',
    ];

    /**
     * Get the lead that owns the e-invoice details.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
