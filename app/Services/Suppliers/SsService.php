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
        $title = $first['brandName'].' '.$meta['title'];
        $style = $first['styleName'] ?? ($first['style'] ?? ($meta['styleName'] ?? ($meta['style'] ?? '')));
        $img = $this->pickImageUrl($first);
        $slug  = Str::slug(trim($brand.' '.$style.' '.$styleID));
        // Build description from meta (best-effort)
        $descPieces = [];
        foreach (['description','catalogDescription','styleDescription','features','feature','fabric','material'] as $k) {
            if (!empty($meta[$k])) {
                $descPieces[] = is_array($meta[$k]) ? implode(', ', $meta[$k]) : (string)$meta[$k];
            }
        }
        $description = trim(implode("\n\n", array_filter($descPieces)));

        // Group by color
        $byColor = [];
        foreach ($variants as $v) {
            if (!is_array($v)) continue;

            $colorCode = $v['colorCode'] ?? ($v['color'] ?? ($v['colorName'] ?? 'UNKNOWN'));
            $colorName = $v['colorName'] ?? (is_string($colorCode) ? $colorCode : 'Color');
            $hex       = $this->sanitizeHex($v['color1'] ?? ($v['hex'] ?? null));
            $colorImg  = $this->pickImageUrl($v);

            $sizeName = $v['size'] ?? ($v['sizeName'] ?? ($v['label'] ?? null));
            if (!isset($byColor[$colorCode])) {
                $byColor[$colorCode] = [
                    'name'  => $colorName,
                    'hex'   => $hex,
                    'image' => $colorImg,
                    'sizes' => [],
                ];
            }

            if ($sizeName) {
                $byColor[$colorCode]['sizes'][(string)$sizeName] = [
                    'label'            => (string)$sizeName,
                    'price_diff_cents' => 0,
                    'stock_qty'        => isset($v['qty']) ? (int)$v['qty'] : null,
                    'sku'              => $v['sku'] ?? ($v['upc'] ?? null),
                    'sort_order'       => $this->sizeOrder((string)$sizeName),
                ];
            }
        }

        // Flatten + sort sizes; then sort colors
        $colors = [];
        foreach ($byColor as $c) {
            $sizes = array_values($c['sizes']);
            usort($sizes, fn($a,$b) => ($a['sort_order'] <=> $b['sort_order']) ?: strcmp($a['label'], $b['label']));
            $c['sizes'] = $sizes;
            $colors[] = $c;
        }
        usort($colors, fn($a,$b) => strcmp($a['name'], $b['name']));

        return [
            'title'            => $title,
            'slug'             => $slug,
            'base_price_cents' => 0,
            'description'      => ($description !== '') ? $description : null,
            'supplier'         => 's&s_products',   // <<< ensure supplier is S&S
            'image'            => $img,
            'colors'           => $colors,
        ];
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
