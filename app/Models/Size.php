<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $fillable = ['label','sort_order'];

    public function productColors()
    {
        return $this->belongsToMany(ProductColor::class, 'product_color_sizes')
            ->using(ProductColorSize::class)
            ->withPivot(['price_diff_cents','stock_qty','sku','active','sort_order'])
            ->withTimestamps();
    }
}
