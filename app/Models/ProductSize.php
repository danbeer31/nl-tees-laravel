<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    protected $fillable = ['product_id','label','sort_order','price_diff_cents'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
