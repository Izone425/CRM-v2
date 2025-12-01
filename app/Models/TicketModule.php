<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketModule extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'modules';

    protected $fillable = ['name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_has_modules', 'module_id', 'product_id');
    }

    public function hasProducts()
    {
        return $this->hasMany(ProductHasModule::class, 'module_id');
    }
}
