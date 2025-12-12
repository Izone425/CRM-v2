<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debtor extends Model
{
    protected $table = 'debtors';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'debtor_code',
        'debtor_name',
        'tax_entity_id',
    ];
}
