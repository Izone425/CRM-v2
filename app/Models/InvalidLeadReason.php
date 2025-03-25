<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvalidLeadReason extends Model {
    use HasFactory;

    // protected $table = 'invalid_lead_reasons'; // Custom table name

    protected $fillable = ['lead_stage', 'reason'];
}
