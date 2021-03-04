<?php

declare(strict_types=1);

namespace Cooler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;

class BCService
{
    const API_URL = 'https://api.bigcommerce.com/stores/';
    const BC_HEADERS = [
        'X-Auth-Client' => 'cdvg04j6qg6wqyrv07tlszt6uyzu5ia',
        'X-Auth-Token' => '4glcdmnprqzbomc0antzo0eknlkrpr4',
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    private $storeHash = '';
    private $client = null;

    /**
     * @param string $storeHash
     */
    public function __construct(string $storeHash)
    {
        $this->storeHash = $storeHash;
        $this->client = new Client();
    }

    /**
     * @param int $orderId
     * @return string
     * @throws GuzzleException
     */
    public function retrieveOrderCurrency(int $orderId): string
    {
        $response = $this->client->request(
            'GET',
            self::API_URL.$this->storeHash.'/v2/orders/'.$orderId,
            ['headers' => self::BC_HEADERS]
        );

        if ($response->getStatusCode() !== 200) {
            throw new InvalidArgumentException('Something went wrong retrieving the Footprint');
        }

        $body = json_decode($response->getBody()->getContents());
        return $body->currency_code;
    }

    /**
     * @param int $orderId
     * @return array
     * @throws GuzzleException
     */
    public function retrieveOrderProductsInfo(int $orderId): array
    {
        $response = $this->client->request(
            'GET',
            self::API_URL.$this->storeHash.'/v2/orders/'.$orderId.'/products',
            ['headers' => self::BC_HEADERS]
        );

        if ($response->getStatusCode() !== 200) {
            throw new InvalidArgumentException('Something went wrong retrieving the Footprint');
        }

        $itemsInfo = [];

        foreach (json_decode($response->getBody()->getContents(), true) as $product) {
            $itemsInfo[] = [
                'product_id' => $product['sku'],
                'quantity' => $product['quantity'],
                'price' => round($product['total_inc_tax'], 0)
            ];
        }

        return $itemsInfo;
    }
}
