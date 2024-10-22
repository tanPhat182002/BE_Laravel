<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotions extends Model
{
    protected $fillable =
    [
        'name',
        'description',
       'discount_type',
       'discount_value',
        'start_date',
        'end_date',
        'is_active'

    ];
    protected $casts =
    [
        'discount_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    
}
