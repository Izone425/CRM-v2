<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemQuestionPhase3 extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'plan',
        'finalise',
        'vendor',
        'percentage',
        'additional',
        'causer_name',
    ];

    /**
     * Convert all attributes to uppercase when retrieving.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (is_string($value)) {
            return strtoupper($value);
        }

        return $value;
    }

    /**
     * Convert all attributes to uppercase before storing in the database.
     */
    public function setAttribute($key, $value)
    {
        if (is_string($value)) {
            $value = strtoupper($value);
        }

        parent::setAttribute($key, $value);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'id');
    }
}
