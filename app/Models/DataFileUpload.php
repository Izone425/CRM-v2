<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataFileUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'file_path',
        'company_id',
        'module',
        'file_type',
        'category',
        'status',
        'uploaded_by',
        'processed_at',
        'result_log',
    ];

    /**
     * Get the user who uploaded the file
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the company related to this file
     */
    public function companyDetail()
    {
        return $this->belongsTo(CompanyDetail::class, 'company_id');
    }
}
