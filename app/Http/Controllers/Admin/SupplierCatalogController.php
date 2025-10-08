<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\Size;

use App\Services\Suppliers\SanmarService;
use App\Services\Suppliers\SsService;

class SupplierCatalogController extends Controller
{
    /* =========================
     * S&S Activewear (styleID + CDN image via SsService)
     * ========================= */

    /** GET /admin/catalog/ss?search=... */
    public function ssIndex(Request $r, SsService $ss)
    {
        $term = trim((string)$r->query('search',''));
        $results = [];

        if ($term !== '') {
            try {
                // SsService returns [{styleID, brand, title, image}]
                $results = $ss->search($term);
            } catch (\Throwable $e) {
                return view('admin.suppliers.ss.index', [
                    'Title'   => 'S&S Catalog',
                    'term'    => $term,
                    'error'   => 'S&S search failed: '.$e->getMessage(),
                    'results' => [],
                ]);
            }
        }

        return view('admin.suppliers.ss.index', [
            'Title'   => 'S&S Catalog',
            'term'    => $term,
            'results' => $results,
        ]);
    }

    /** POST /admin/catalog/ss/import (expects styleID) */
    public function ssImport(Request $r, SsService $ss)
    {
        $styleID = (string)$r->input('styleID');
        abort_if($styleID === '', 400, 'Missing styleID');

        try {
            $variants   = $ss->getVariants($styleID);
            $meta       = $ss->getStyleMeta($styleID);                 // 👈 NEW
            $normalized = $ss->normalizeForImport($variants, $styleID, $meta); // 👈 pass meta
            $product    = $this->importNormalizedProduct($normalized);

            return back()->with('success', "Imported S&S styleID {$styleID} → Product #{$product->id}");
        } catch (\Throwable $e) {
            return back()->with('error', 'S&S import failed: '.$e->getMessage());
        }
    }

    /* =========================
     * SanMar (unchanged flow via SanmarService)
     * ========================= */

    public function sanmarIndex(Request $r, SanmarService $sanmar)
    {
        $term = trim((string)$r->query('search',''));
        $results = [];

        if ($term !== '') {
            try {
                $results = $sanmar->search($term);
            } catch (\Throwable $e) {
                return view('admin.suppliers.sanmar.index', [
                    'Title'   => 'SanMar Catalog',
                    'term'    => $term,
                    'error'   => 'SanMar search failed: '.$e->getMessage(),
                    'results' => [],
                ]);
            }
        }

        return view('admin.suppliers.sanmar.index', [
            'Title'   => 'SanMar Catalog',
            'term'    => $term,
            'results' => $results,
        ]);
    }

    public function sanmarImport(Request $r, SanmarService $sanmar)
    {
        $style = (string)$r->input('style');
        abort_if($style === '', 400, 'Missing style');

        try {
            $records    = $sanmar->getByStyle($style);  // array of style-color-size payloads
            $normalized = $this->normalizeSanmar($records);
            $product    = $this->importNormalizedProduct($normalized);

            return back()->with('success', "Imported SanMar style {$style} → Product #{$product->id}");
        } catch (\Throwable $e) {
            return back()->with('error', 'SanMar import failed: '.$e->getMessage());
        }
    }

    private function normalizeSanmar(array $records): array
    {
        $basic = $records[0]['productBasicInfo'] ?? [];
        $title = $basic['productTitle'] ?? ($basic['style'] ?? 'Unknown');
        $brand = $basic['brandName'] ?? '';
        $style = $basic['style'] ?? 'sanmar-style';
        $slug  = Str::slug(trim($brand.' '.$title.' '.$style));

        $colorsByName = [];
        foreach ($records as $row) {
            $colorName = ($row['colorInfo']['productColorName'] ?? '') ?: ($row['colorInfo']['colorName'] ?? '');
            if ($colorName === '') continue;

            $sizeLabel = $row['sizeInfo']['size'] ?? null;

            if (!isset($colorsByName[$colorName])) {
                $colorsByName[$colorName] = ['name'=>$colorName,'hex'=>null,'sizes'=>[]];
            }
            if ($sizeLabel) {
                $colorsByName[$colorName]['sizes'][$sizeLabel] = ['label'=>$sizeLabel, 'price_diff_cents'=>0];
            }
        }

        $colors = array_map(function($c){
            $c['sizes'] = array_values($c['sizes']);
            return $c;
        }, array_values($colorsByName));

        return [
            'title'             => $title,
            'slug'              => $slug,
            'base_price_cents'  => 0,
            'colors'            => $colors,
        ];
    }

    /* =========================
     * Shared import (Option B: master sizes + pivot)
     * ========================= */
    private function importNormalizedProduct(array $data): \App\Models\Product
    {
        return DB::transaction(function () use ($data) {
            /** @var Product $product */
            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title'            => $data['title'],
                    'description'      => $data['description'] ?? null,
                    'base_price_cents' => $data['base_price_cents'] ?? 0,
                    'supplier'         => $data['supplier'] ?? null, // might be ignored if not fillable
                    'image_url'        => $data['image'] ?? null,
                    'active'           => true,
                ]
            );
// ✅ Force supplier to the incoming value even if fillable/observers set something else
            if (!empty($data['supplier']) && $product->supplier !== $data['supplier']) {
                $product->supplier = $data['supplier'];
                $product->save();
            }

            // Colors
            $sort = 1;
            foreach ($data['colors'] as $c) {
                /** @var ProductColor $color */
                $color = $product->colors()->updateOrCreate(
                    ['name' => $c['name']],
                    [
                        'hex'               => $c['hex'] ?? null,
                        'sort_order'        => $sort++,
                        'price_diff_cents'  => $c['price_diff_cents'] ?? 0,
                        // If you have a column for image url on colors, map it here:
                        // 'image_url'      => $c['image'] ?? null,
                    ]
                );

                // Sizes (master table + pivot)
                $i = 1;
                foreach ($c['sizes'] as $s) {
                    $size = Size::firstOrCreate(
                        ['label' => $s['label']],
                        ['sort_order' => $i] // used if creating new size labels
                    );

                    $color->sizes()->syncWithoutDetaching([
                        $size->id => [
                            'sort_order'       => $s['sort_order'] ?? $i,
                            'price_diff_cents' => $s['price_diff_cents'] ?? 0,
                            'stock_qty'        => $s['stock_qty'] ?? null,
                            'sku'              => $s['sku'] ?? null,
                            'active'           => true,
                        ]
                    ]);
                    $i++;
                }
            }

            return $product->fresh(['colors','colors.sizes']);
        });
    }
}
