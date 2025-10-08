<?php

namespace App\Http\Controllers;

use App\Models\Product;

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
    public function show(Product $product)
    {
        $product->load([
            'colors' => fn($q) => $q->orderBy('sort_order'),
            // nested eager load of sizes on each color
            'colors.sizes' => fn($q) => $q->orderBy('sizes.sort_order'),
        ]);

        return view('shop.product', [
            'Title'   => $product->title,
            'product' => $product,
        ]);
    }
    use Illuminate\Http\Request;
    use App\Models\ProductColor; // or your namespace

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
