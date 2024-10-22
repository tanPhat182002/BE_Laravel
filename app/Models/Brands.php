<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brands extends Model
{
    protected $fillable = ['name', 'image'];

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
