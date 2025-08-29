<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Policy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'category_id',
        'summary', // Optional summary field
        'effective_date',
        'attachment_path',
        'status',
        'created_by',
        'last_updated_by'
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    /**
     * Get the pages for this policy
     */
    public function pages()
    {
        return $this->hasMany(PolicyPage::class)->orderBy('order');
    }

    /**
     * Get the category that owns the policy
     */
    public function category()
    {
        return $this->belongsTo(PolicyCategory::class, 'category_id');
    }

    /**
     * Get the user who created the policy
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the policy
     */
    public function lastUpdatedByUser()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
}
