<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title','slug','description','image_url','base_price_cents','supplier','active'
    ];

    public function colors()
    {
        return $this->hasMany(ProductColor::class)->orderBy('sort_order');
    }

    public function sizes()
    {
        return $this->belongsToMany(Size::class, 'product_color_sizes')
            ->using(\App\Models\ProductColorSize::class)
            ->withPivot(['price_diff_cents','stock_qty','sku','active','sort_order'])
            ->withTimestamps()
            ->orderBy('product_color_sizes.sort_order')
            ->orderBy('sizes.sort_order');
    }

    // convenience
    public function getBasePriceAttribute(): float
    {
        return $this->base_price_cents / 100;
    }

    // for route model binding by slug (optional helper)
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
