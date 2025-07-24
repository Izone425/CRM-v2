<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{
    use HasFactory;

    protected $table = 'resellers'; // explicitly define the table

    protected $fillable = [
        'company_name'
    ];

    public function leads()
    {
        return $this->hasMany(Lead::class, 'reseller_id');
    }
}
