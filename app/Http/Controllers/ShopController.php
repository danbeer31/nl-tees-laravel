<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    // List products (customer homepage)
    public function home()
    {
        $products = Product::query()
            ->where('active', true)
            ->orderBy('id')
            ->get(['id','title','slug','base_price_cents','image_url','supplier']);

        return view('shop.home', [
            'Title'    => 'Welcome',
            'products' => $products,
        ]);
    }

    // Product detail: eager-load colors + sizes (normalized, via pivot)
    // app/Http/Controllers/ShopController.php

    public function show(string $slug)
    {
        // 1) Load product with colors and sizes. Do NOT eager-load product->images.
        $product = \App\Models\Product::query()
            ->where('slug', $slug)
            ->with([
                'colors' => fn($q) => $q->orderBy('sort_order'),
                'colors.sizes' => fn($q) => $q->orderBy('sizes.sort_order'),
            ])
            ->firstOrFail();

        // 2) For each color, resolve its images using the color->images relation if it exists.
        //    Fallback to querying product_images by color_id.
        $imagesByColor = collect($product->colors ?: [])->mapWithKeys(function ($color) {
            // Determine the key we expose to the view
            $colorKey = $color->color_key ?? $color->id;

            // Try relation names commonly used
            $images = collect();
            if (method_exists($color, 'images')) {
                $color->loadMissing('images');
                $images = collect($color->images);
            } elseif (method_exists($color, 'productImages')) {
                $color->loadMissing('productImages');
                $images = collect($color->productImages);
            } else {
                // Fallback direct query. Adjust table/columns if your schema differs.
                if (class_exists(\App\Models\ProductImage::class)) {
                    $images = \App\Models\ProductImage::query()
                        ->where('color_id', $color->id)
                        ->orderBy('sort_order')
                        ->get();
                } else {
                    $images = \Illuminate\Support\Facades\DB::table('product_images')
                        ->where('color_id', $color->id)
                        ->orderBy('sort_order')
                        ->get();
                }
            }

            // Normalize payload for the view
            $normalized = $images->map(function ($img) {
                return [
                    'url'  => $img->url ?? ($img->path ?? ''),   // adjust if you store paths differently
                    'type' => $img->type ?? 'default',
                    'alt'  => $img->alt  ?? '',
                ];
            })->values();

            return [$colorKey => $normalized];
        });

        // 3) Build sizes per color for the view
        $sizesByColor = collect($product->colors ?: [])->mapWithKeys(function ($color) {
            $sizes = collect($color->sizes ?: [])->map(fn($s) => [
                'id'          => $s->id,
                'name'        => $s->name ?? $s->label ?? '',
                'sku'         => $s->sku ?? null,
                'price_delta' => $s->price_delta ?? 0,
                'sort_order'  => $s->sort_order ?? 0,
            ])->values();

            $key = $color->color_key ?? $color->id;
            return [$key => $sizes];
        });

        return view('shop.product', [
            'product'       => $product,
            'colors'        => $product->colors,
            'imagesByColor' => $imagesByColor,
            'sizesByColor'  => $sizesByColor,
        ]);
    }



    public function colorData(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required','integer'],
            'color_id'   => ['required','integer'],
        ]);

        $color = ProductColor::query()
            ->with([
                'product',
                'images' => fn($q) => $q->orderBy('sort_order'),
                'sizes'  => fn($q) => $q->orderBy('sort_order')->orderBy('name'),
            ])
            ->where('product_id', $data['product_id'])
            ->where('id', $data['color_id'])
            ->first();

        if (!$color) {
            return response()->json(['ok'=>false,'error'=>'Color not found for this product.'], 404);
        }

        $images = $color->images->map(fn($i) => [
            'id'  => $i->id,
            'url' => $i->url ?? $i->path ?? '',
            'alt' => $i->alt ?? '',
        ])->values();

        $sizes = $color->sizes->map(fn($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'price_delta' => (float)($s->price_difference ?? 0),
            'in_stock'    => (bool)($s->in_stock ?? true),
        ])->values();

        return response()->json([
            'ok'   => true,
            'data' => [
                'color' => [
                    'id'          => $color->id,
                    'name'        => $color->name,
                    'hex'         => $color->hex,
                    'price_delta' => (float)($color->price_difference ?? 0),
                ],
                'product' => [
                    'id'         => $color->product->id,
                    'base_price' => (float)($color->product->price ?? 0),
                ],
                'images' => $images,
                'sizes'  => $sizes,
            ]
        ]);
    }
}
