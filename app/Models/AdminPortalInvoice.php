<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPortalInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'finance_invoice_id',
        'reseller_name',
        'subscriber_name',
        'autocount_invoice',
        'tt_invoice',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['formatted_id'];

    public function getFormattedIdAttribute()
    {
        if (!$this->id || !$this->created_at) {
            return null;
        }

        $year = $this->created_at->format('y'); // Get last 2 digits of year

        // Get the sequential number for this year
        $yearStart = $this->created_at->copy()->startOfYear();
        $yearEnd = $this->created_at->copy()->endOfYear();

        $sequentialNumber = self::whereBetween('created_at', [$yearStart, $yearEnd])
            ->where('id', '<=', $this->id)
            ->count();

        return 'FD_' . $year . str_pad($sequentialNumber, 5, '0', STR_PAD_LEFT);
    }

    public function financeInvoice()
    {
        return $this->belongsTo(FinanceInvoice::class);
    }
}
