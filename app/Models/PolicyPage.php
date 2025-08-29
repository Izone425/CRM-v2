<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PolicyPage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'policy_id',
        'title',
        'content',
        'order',
        'created_by',
        'last_updated_by'
    ];

    /**
     * Get the policy that owns this page
     */
    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    /**
     * Get the user who created the page
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the page
     */
    public function lastUpdatedByUser()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
}
