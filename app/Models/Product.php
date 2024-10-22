<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'category_id',
        'brand_id',
        'is_active'
    ];
  
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
    public function brand()
    {
        return $this->belongsTo(Brands::class);
    }

    public function category()
    {
        return $this->belongsTo(Categories::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotions::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImages::class);
    }
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

}