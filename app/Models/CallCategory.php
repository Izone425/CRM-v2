<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'tier',
        'parent_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class, 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CallCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CallCategory::class, 'parent_id');
    }
}
