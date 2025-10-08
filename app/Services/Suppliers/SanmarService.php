<?php

namespace App\Services\Suppliers;

use SoapClient;

class SanmarService
{
    public function __construct(
        private readonly string $base = 'https://ws.sanmar.com:8080/',
        private readonly string $custNum = '',
        private readonly string $user = '',
        private readonly string $pass = '',
    ) {}

    private function client(string $endpoint): SoapClient
    {
        return new SoapClient($this->base . $endpoint, [
            'exceptions' => true,
            'trace'      => false,
            'cache_wsdl' => WSDL_CACHE_BOTH,
        ]);
    }

    /** Search by style (or keyword if the WSDL supports it) */
    public function search(string $term): array
    {
        if ($term === '') return [];
        $c = $this->client('SanMarWebService/SanMarProductInfoServicePort?wsdl');
        $resp = $c->getProductInfoByStyleColorSize([
            'arg0' => ['style' => $term, 'color' => null, 'size' => null],
            'arg1' => [
                'sanMarCustomerNumber' => $this->custNum,
                'sanMarUserName'       => $this->user,
                'sanMarUserPassword'   => $this->pass,
            ],
        ]);
        $arr = json_decode(json_encode($resp), true);
        $list = $arr['return']['listResponse'] ?? [];
        // Normalize a few fields we care about
        return array_map(function ($raw) {
            $basic  = $raw['productBasicInfo'] ?? [];
            $imgs   = $raw['productImageInfo'] ?? [];
            return [
                'style'   => $basic['style'] ?? '',
                'brand'   => $basic['brandName'] ?? '',
                'title'   => $basic['productTitle'] ?? ($basic['style'] ?? ''),
                'image'   => $imgs['productImage'] ?? ($imgs['brandLogoImage'] ?? null),
                'raw'     => $raw, // keep full payload for import
            ];
        }, is_assoc($list) ? [$list] : $list);
    }

    public function getByStyle(string $style): array
    {
        $c = $this->client('SanMarWebService/SanMarProductInfoServicePort?wsdl');
        $resp = $c->getProductInfoByStyleColorSize([
            'arg0' => ['style' => $style, 'color' => null, 'size' => null],
            'arg1' => [
                'sanMarCustomerNumber' => $this->custNum,
                'sanMarUserName'       => $this->user,
                'sanMarUserPassword'   => $this->pass,
            ],
        ]);
        $arr = json_decode(json_encode($resp), true);
        $list = $arr['return']['listResponse'] ?? [];
        return is_assoc($list) ? [$list] : $list;
    }
}

// tiny helper
if (!function_exists('is_assoc')) {
    function is_assoc(array $a): bool { return array_keys($a) !== range(0, count($a)-1); }
}
