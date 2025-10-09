<?php

namespace App\Services\Suppliers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SsService
{
    /** Base API + CDN */
    private string $base = 'https://api.ssactivewear.com/v2';
    public static string $img_loc = 'https://cdn.ssactivewear.com/';

    /** Candidate image fields to try (in order) */
    private array $imageFields = [
        'styleImage', 'colorFrontImage', 'colorSideImage', 'colorBackImage', 'image', 'frontImage'
    ];

    public function __construct(
        private readonly ?string $user = null,
        private readonly ?string $pass = null,
    ) {
        $this->base = rtrim($this->base, '/');
    }

    /** Search styles (idempotent GET). Returns [{styleID, brand, title, image}] */
    public function search(string $term): array
    {
        $term = trim($term);
        if ($term === '') return [];

        $res = $this->http()->get($this->base . '/styles', ['search' => $term]);
        if (!$res->ok()) return [];

        $items = $res->json() ?? [];
        $out   = [];

        foreach ((array)$items as $row) {
            $styleID = $row['styleID'] ?? ($row['styleId'] ?? $row['styleid'] ?? null);
            if (!$styleID) continue;

            $brand = $row['brandName'] ?? ($row['brand'] ?? '');
            $title = $row['title'] ?? ($row['styleName'] ?? (string)$styleID);
            $image = $this->pickImageUrl($row);

            $out[] = [
                'styleID' => (string)$styleID,
                'brand'   => $brand,
                'title'   => $title,
                'image'   => $image,
            ];
        }

        return $out;
    }

    /** Fetch product variants for a style (GET /products?styleid=...). Returns array of variant arrays. */
    public function getVariants(string $styleID): array
    {
        $styleID = trim($styleID);
        if ($styleID === '') return [];

        $res = $this->http()->get($this->base . '/products', ['styleid' => $styleID]);
        if (!$res->ok()) return [];

        $data = $res->json() ?? [];
        return is_array($data) ? array_values($data) : [];
    }

    /** Fetch style meta (title/brand/description fields) */
    public function getStyleMeta(string $styleID): array
    {
        $styleID = trim($styleID);
        if ($styleID === '') return [];
        $res = $this->http()->get($this->base . '/styles', ['styleid' => $styleID]);
        if (!$res->ok()) return [];
        $list = $res->json() ?? [];
        if (is_array($list)) {
            $first = $list[0] ?? null;
            return is_array($first) ? $first : (is_object($first) ? (array)$first : []);
        }
        return [];
    }

    /**
     * Convert S&S variants into your importer shape (Option B schema), with description + supplier.
     * Returns:
     * [
     *   'title','slug','base_price_cents','description','supplier',
     *   'colors' => [ {name,hex,image,sizes:[{label,price_diff_cents,stock_qty,sku,sort_order}]}... ]
     * ]
     */
    public function normalizeForImport(array $variants, string $styleID, array $meta = []): array
    {
        $first = $variants[0] ?? [];

        $brand = $first['brandName'] ?? ($first['brand'] ?? ($meta['brandName'] ?? ($meta['brand'] ?? '')));
        $style = $first['styleName'] ?? ($first['style'] ?? ($meta['styleName'] ?? ($meta['style'] ?? (string)$styleID)));
        $title = trim((string)($meta['title'] ?? $first['title'] ?? "$brand $style"));

        $slug  = Str::slug(trim("$brand $style $styleID"));
        $img   = $this->pickImageUrl($first);

        // Description
        $descPieces = [];
        foreach (['description','catalogDescription','styleDescription','features','feature','fabric','material'] as $k) {
            if (!empty($meta[$k])) $descPieces[] = is_array($meta[$k]) ? implode(', ', $meta[$k]) : (string)$meta[$k];
        }
        $description = trim(implode("\n\n", array_filter($descPieces)));

        // Group by color
        $byColor = [];
        foreach ($variants as $v) {
            if (!is_array($v)) continue;

            $colorCode = (string)($v['colorCode'] ?? $v['color'] ?? $v['colorName'] ?? 'UNKNOWN');
            $colorName = (string)($v['colorName'] ?? $colorCode);
            $hex       = $this->sanitizeHex($v['color1'] ?? ($v['hex'] ?? null));

            if (!isset($byColor[$colorCode])) {
                $byColor[$colorCode] = [
                    'name'   => $colorName,
                    'hex'    => $hex,
                    'images' => [],   // will be array of {path,alt,sort_order,is_primary,meta?}
                    '_seen'  => [],   // dedupe helper
                    'sizes'  => [],
                ];
            } elseif ($hex && empty($byColor[$colorCode]['hex'])) {
                $byColor[$colorCode]['hex'] = $hex;
            }

            // collect images from this variant
            foreach ($this->collectImages($v, $title, $colorName) as $obj) {
                $u = $obj['path'];
                if (isset($byColor[$colorCode]['_seen'][$u])) continue;
                $byColor[$colorCode]['_seen'][$u] = true;

                $obj['sort_order'] = count($byColor[$colorCode]['images']) + 1;
                $obj['is_primary'] = $obj['sort_order'] === 1;
                $byColor[$colorCode]['images'][] = $obj;
            }

            // sizes
            $sizeName = $v['size'] ?? ($v['sizeName'] ?? ($v['label'] ?? null));
            if ($sizeName) {
                $label = (string)$sizeName;
                $byColor[$colorCode]['sizes'][$label] = [
                    'label'            => $label,
                    'price_diff_cents' => 0,
                    'stock_qty'        => isset($v['qty']) ? (int)$v['qty'] : null,
                    'sku'              => $v['sku'] ?? ($v['upc'] ?? null),
                    'sort_order'       => $this->sizeOrder($label),
                ];
            }
        }

        // Normalize arrays and back-compat 'image' field
        $colors = [];
        foreach ($byColor as $c) {
            unset($c['_seen']);
            $c['sizes']  = array_values($c['sizes']);
            usort($c['sizes'], fn($a,$b)=>($a['sort_order']<=>$b['sort_order']) ?: strcmp($a['label'],$b['label']));
            $c['images'] = array_values($c['images']);
            $c['image']  = $c['images'][0]['path'] ?? null; // optional legacy field
            $colors[] = $c;
        }
        usort($colors, fn($a,$b)=>strcmp($a['name'],$b['name']));

        return [
            'title'            => $title,
            'slug'             => $slug,
            'base_price_cents' => 0,
            'description'      => $description !== '' ? $description : null,
            'supplier'         => 's&s_products',
            'image'            => $img,
            'colors'           => $colors,
        ];
    }

    /** Build image objects from a variant row */
    private function collectImages(array $row, string $title = '', string $colorName = ''): array
    {
        $urls = [];

        // main candidates
        foreach (['styleImage','colorFrontImage','colorSideImage','colorBackImage','image','frontImage'] as $f) {
            if (!empty($row[$f])) $urls[] = $this->cdnUrl($row[$f]);
        }
        // swatch-like fields (if present) tagged in meta
        $swatch = null;
        foreach (['swatchImage','colorSwatchImage','swatch'] as $f) {
            if (!empty($row[$f])) { $swatch = $this->cdnUrl($row[$f]); break; }
        }

        // dedupe while building objects
        $out = [];
        $seen = [];
        $i = 0;
        foreach ($urls as $u) {
            if (!$u || isset($seen[$u])) continue;
            $seen[$u] = true;
            $i++;
            $out[] = [
                'path'       => $u,
                'alt'        => trim($title ? "$title - $colorName" : $colorName),
                'sort_order' => $i,
                'is_primary' => $i === 1,
            ];
        }

        if ($swatch && !isset($seen[$swatch])) {
            $out[] = [
                'path'       => $swatch,
                'alt'        => trim($title ? "$title - $colorName swatch" : "$colorName swatch"),
                'sort_order' => count($out) + 1,
                'is_primary' => false,
                'meta'       => ['type' => 'swatch'],
            ];
        }

        return $out;
    }


    /* =============== helpers =============== */

    private function http()
    {
        $user = $this->user ?? config('services.ss.user') ?? env('SS_USER');
        $pass = $this->pass ?? config('services.ss.pass') ?? env('SS_PASS');

        return Http::withBasicAuth((string)$user, (string)$pass)
            ->acceptJson()
            ->timeout(30);
    }

    private function pickImageUrl(array $row): ?string
    {
        foreach ($this->imageFields as $f) {
            if (!empty($row[$f])) return $this->cdnUrl($row[$f]);
        }
        if (!empty($row['image'])) return $this->cdnUrl($row['image']);
        if (!empty($row['frontImage'])) return $this->cdnUrl($row['frontImage']);
        return null;
    }

    private function cdnUrl(?string $relative): ?string
    {
        if (!$relative) return null;
        return rtrim(self::$img_loc, '/') . '/' . ltrim($relative, '/');
    }

    private function sanitizeHex(?string $hex): ?string
    {
        if (!$hex) return null;
        $hex = ltrim($hex, '#');
        return preg_match('/^[0-9A-Fa-f]{6}$/', $hex) ? '#'.$hex : null;
    }

    /** Basic size ordering: XS → S → M → L → XL → 2XL → ... */
    private function sizeOrder(string $label): int
    {
        static $map = [
            'XS'=>1,'XSM'=>1,'YXS'=>1,
            'S'=>2,'SM'=>2,'SMALL'=>2,'YS'=>2,
            'M'=>3,'MED'=>3,'MEDIUM'=>3,'YM'=>3,
            'L'=>4,'LRG'=>4,'LARGE'=>4,'YL'=>4,
            'XL'=>5,'X-LARGE'=>5,'YXL'=>5,
            '2XL'=>6,'XXL'=>6,
            '3XL'=>7,'XXXL'=>7,
            '4XL'=>8,'XXXXL'=>8,
            '5XL'=>9,'XXXXXL'=>9,
        ];
        $u = strtoupper(trim($label));
        if (isset($map[$u])) return $map[$u];
        if (preg_match('/^(\d+)\s*X(L)?$/i', $u, $m)) {
            return 5 + max(1, (int)$m[1]);
        }
        return 100;
    }
}
