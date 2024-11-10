<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'category_id',
        'brand_id',
        'is_active',
        'promotion_id'  // Thêm promotion_id vào fillable
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brands::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotions::class, 'promotion_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImages::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function approvedRatings()
    {
        return $this->hasMany(Rating::class)->where('is_approved', true);
    }

    // Get average rating
    public function getAverageRatingAttribute()
    {
        return $this->approvedRatings()->avg('star_rating') ?? 0;
    }

    // Get total ratings count
    public function getTotalRatingsAttribute()
    {
        return $this->approvedRatings()->count();
    }
}