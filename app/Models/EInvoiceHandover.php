<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EInvoiceHandover extends Model
{
    use HasFactory;

    protected $table = 'einvoice_handovers';

    protected $fillable = [
        'lead_id',
        'subsidiary_id',
        'salesperson',
        'company_name',
        'company_type',
        'status',
        'created_by',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    /**
     * Generate project code based on creation year and ID
     * Format: FA_YYXXXX (where YY is year and XXXX is padded ID)
     *
     * @return string
     */
    public function getProjectCodeAttribute(): string
    {
        // Handle case where created_at might be null
        if (!$this->created_at) {
            $year = substr(\Carbon\Carbon::now()->format('Y'), -2); // Use current year as fallback
        } else {
            $year = substr($this->created_at->format('Y'), -2); // Get last 2 digits of year
        }

        $paddedId = str_pad($this->id, 4, '0', STR_PAD_LEFT); // Pad ID to 4 digits
        return "FA_{$year}{$paddedId}";
    }

    /**
     * Static method to generate project code for any E-Invoice handover
     *
     * @param int $handoverId
     * @param string|null $createdAt
     * @return string
     */
    public static function generateProjectCode(int $handoverId, string $createdAt = null): string
    {
        $year = $createdAt
            ? substr(\Carbon\Carbon::parse($createdAt)->format('Y'), -2)
            : substr(\Carbon\Carbon::now()->format('Y'), -2);

        $paddedId = str_pad($handoverId, 4, '0', STR_PAD_LEFT);
        return "FA_{$year}{$paddedId}";
    }

    /**
     * Get the lead that owns the E-Invoice handover.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user who created the E-Invoice handover.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
