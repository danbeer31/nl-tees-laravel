<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductColor extends Model
{
    protected $fillable = [
        'product_id','name','hex','sort_order','price_diff_cents','active'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ✅ Needed for S&S import (Option B schema)
    public function sizes()
    {
        return $this->belongsToMany(Size::class, 'product_color_sizes')
            ->withPivot(['price_diff_cents','stock_qty','sku','active','sort_order'])
            ->withTimestamps()
            ->orderBy('product_color_sizes.sort_order')
            ->orderBy('sizes.sort_order');
    }
    // app/Models/ProductColor.php
    public function images()
    {
        return $this->hasMany(\App\Models\ProductColorImage::class)
            ->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(\App\Models\ProductColorImage::class)->where('is_primary', true);
    }
}
