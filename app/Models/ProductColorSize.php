<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductColorSize extends Pivot
{
    protected $table = 'product_color_sizes';

    protected $fillable = [
        'product_color_id','size_id','price_diff_cents','stock_qty','sku','active','sort_order'
    ];
}
