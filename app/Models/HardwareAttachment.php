<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HardwareAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'hardware_handover_id',
        'title',
        'description',
        'files',
        'created_by',
        'updated_by',
    ];

    // Remove the array cast since we'll handle this manually
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Process the files attribute to ensure consistent format
     *
     * @param mixed $value
     * @return array
     */
    public function getFilesAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        // If already an array, process each item
        if (is_array($value)) {
            $processed = [];
            foreach ($value as $item) {
                if (is_string($item) && (str_starts_with($item, '[') || str_starts_with($item, '{'))) {
                    // If the item is a JSON string, decode it
                    $decoded = json_decode($item, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $file) {
                            $processed[] = $file;
                        }
                    } else {
                        $processed[] = $item;
                    }
                } else {
                    $processed[] = $item;
                }
            }
            return $processed;
        }

        // If it's a string, try to decode it as JSON
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                // Process the decoded array (might contain JSON strings)
                $processed = [];
                foreach ($decoded as $item) {
                    if (is_string($item) && (str_starts_with($item, '[') || str_starts_with($item, '{'))) {
                        // If the item is a nested JSON string, decode it again
                        $nestedDecoded = json_decode($item, true);
                        if (is_array($nestedDecoded)) {
                            foreach ($nestedDecoded as $file) {
                                $processed[] = $file;
                            }
                        } else {
                            $processed[] = $item;
                        }
                    } else {
                        $processed[] = $item;
                    }
                }
                return $processed;
            }
            // If it's not valid JSON, treat as a single file path
            return [$value];
        }

        // Fallback
        return [];
    }

    /**
     * Set the files attribute, ensuring it's stored as JSON
     *
     * @param mixed $value
     * @return void
     */
    public function setFilesAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['files'] = json_encode($value);
        } else {
            $this->attributes['files'] = $value;
        }
    }

    // Existing relationship methods...
    public function hardwareHandover(): BelongsTo
    {
        return $this->belongsTo(HardwareHandover::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
