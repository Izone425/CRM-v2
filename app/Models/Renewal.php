<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Renewal extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'f_company_id',
        'company_name',
        'expiry_date',
        'pi_numbers',
        'total_amount',
        'mapping_status',
        'admin_renewal',
        'renewal_progress',
        'reseller_status',
        'reseller_name',
        'notes',
        'progress_history',
        'follow_up_date',
        'follow_up_counter',
        'manual_follow_up_count'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'pi_numbers' => 'array',
        'progress_history' => 'array',
        'total_amount' => 'decimal:2',
    ];

    // Relationship to Lead (many renewals to one lead)
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // Status options
    public static function getMappingStatusOptions(): array
    {
        return [
            'pending_mapping' => 'Pending Mapping',
            'completed_mapping' => 'Completed Mapping',
            'onhold_mapping' => 'OnHold Mapping',
        ];
    }

    public static function getRenewalProgressOptions(): array
    {
        return [
            'new' => 'New',
            'pending_confirmation' => 'Pending Confirmation',
            'pending_payment' => 'Pending Payment',
            'completed_renewal' => 'Completed Renewal',
        ];
    }

    public static function getResellerStatusOptions(): array
    {
        return [
            'none' => 'None',
            'available' => 'Available',
        ];
    }

    // Helper methods
    public function getMappingStatusLabelAttribute(): string
    {
        return self::getMappingStatusOptions()[$this->mapping_status] ?? $this->mapping_status;
    }

    public function getAdminRenewalLabelAttribute(): string
    {
        return self::getAdminRenewalOptions()[$this->admin_renewal] ?? $this->admin_renewal;
    }

    public function getRenewalProgressLabelAttribute(): string
    {
        return self::getRenewalProgressOptions()[$this->renewal_progress] ?? $this->renewal_progress;
    }

    public function getResellerStatusLabelAttribute(): string
    {
        return self::getResellerStatusOptions()[$this->reseller_status] ?? $this->reseller_status;
    }

    // Add progress history entry
    public function addProgressHistory(string $from, string $to, string $reason = null): void
    {
        $history = $this->progress_history ?? [];
        $history[] = [
            'from' => $from,
            'to' => $to,
            'reason' => $reason,
            'changed_at' => now()->toISOString(),
            'changed_by' => auth()->user()?->name ?? 'System',
        ];
        $this->update(['progress_history' => $history]);
    }

    // Scope methods
    public function scopeByMappingStatus($query, string $status)
    {
        return $query->where('mapping_status', $status);
    }

    public function scopeByRenewalProgress($query, string $progress)
    {
        return $query->where('renewal_progress', $progress);
    }

    public function scopeExpiringBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('expiry_date', [$startDate, $endDate]);
    }
}
