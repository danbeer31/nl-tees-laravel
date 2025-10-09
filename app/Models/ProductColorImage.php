<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductColorImage extends Model
{
    protected $fillable = [
        'product_color_id',
        'path',        // storage path or URL
        'alt',
        'sort_order',
        'is_primary',
        'meta',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'meta' => 'array',
    ];

    public function color()
    {
        return $this->belongsTo(ProductColor::class, 'product_color_id');
    }

    /* Helpers */
    public function scopePrimary($q)
    {
        return $q->where('is_primary', true);
    }

    public function getUrlAttribute(): string
    {
        // adjust if you store absolute URLs
        return str_starts_with($this->path, 'http')
            ? $this->path
            : asset($this->path);
    }
}

