<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HardwareHandoverV2 extends Model
{
    use HasFactory;

    protected $table = 'hardware_handovers_v2';

    protected $fillable = [
        // Database columns from schema
        'id',
        'lead_id',
        'created_by',
        'status',
        'sales_order_number',
        'handover_pdf',
        'courier',
        'courier_address',
        'installation_type',
        'reject_reason',
        'category2',
        'contact_detail',
        'pic_name',
        'pic_phone',
        'email',
        'installer',
        'reseller',
        'implementer',
        'remarks',
        'admin_remarks',
        'proforma_invoice_hrdf',
        'proforma_invoice_product',
        'tc10_quantity',
        'tc20_quantity',
        'face_id5_quantity',
        'face_id6_quantity',
        'time_beacon_quantity',
        'nfc_tag_quantity',
        'payment_status',
        'device_serials',
        'invoice_type',
        'invoice_data',
        'related_software_handovers',
        'video_files',
        'reseller_invoice',
        'confirmation_order_file',
        'hrdf_grant_file',
        'payment_slip_file',
        'new_attachment_file',
        'invoice_file',
        'sales_order_file',
        'completed_at',
        'submitted_at',
        'pending_stock_at',
        'pending_migration_at',
        'created_at',
        'updated_at',
        'reseller_quotation_file',
    ];

    protected $casts = [
        // Cast JSON-encoded fields as arrays
        'category2' => 'array',
        'video_files' => 'array',
        'confirmation_order_file' => 'array',
        'hrdf_grant_file' => 'array',
        'payment_slip_file' => 'array',
        'proforma_invoice_product' => 'array',
        'proforma_invoice_hrdf' => 'array',
        'new_attachment_file' => 'array',
        'invoice_file' => 'array',
        'sales_order_file' => 'array',
        'related_software_handovers' => 'array',
        'admin_remarks' => 'array',
        'reseller_quotation_file' => 'array',
    ];

    /**
     * Get the lead that owns this hardware handover
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
     * Set the remarks attribute to uppercase.
     *
     * @param mixed $value
     * @return void
     */
    // public function setRemarksAttribute($value)
    // {
    //     if (is_array($value)) {
    //         // If it's an array, uppercase each element's content
    //         foreach ($value as $key => $item) {
    //             if (isset($item['remark']) && is_string($item['remark'])) {
    //                 $value[$key]['remark'] = strtoupper($item['remark']);
    //             }
    //         }
    //         $this->attributes['remarks'] = json_encode($value);
    //     } else if (is_string($value)) {
    //         // If it's already JSON string
    //         if ($this->isJson($value)) {
    //             $decodedValue = json_decode($value, true);
    //             foreach ($decodedValue as $key => $item) {
    //                 if (isset($item['remark']) && is_string($item['remark'])) {
    //                     $decodedValue[$key]['remark'] = strtoupper($item['remark']);
    //                 }
    //             }
    //             $this->attributes['remarks'] = json_encode($decodedValue);
    //         } else {
    //             // If it's a plain string, just uppercase it
    //             $this->attributes['remarks'] = strtoupper($value);
    //         }
    //     } else {
    //         // Otherwise, just set it as is
    //         $this->attributes['remarks'] = $value;
    //     }
    // }

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
}
