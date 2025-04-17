<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoRanking extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'rank'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}