<?php

declare(strict_types=1);

namespace Cooler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;

class BCService
{
    const API_URL = 'https://api.bigcommerce.com/stores/';
    const BC_HEADERS = [
        'X-Auth-Client' => 'cdvg04j6qg6wqyrv07tlszt6uyzu5ia',
        'X-Auth-Token' => 'llvfwcb80glkixtq1qaep88bfv0yjrz',
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    private $storeHash = '';
    private $client;

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

    /**
     * @param string $cartId
     * @return array
     * @throws GuzzleException
     */
    public function mapCartInformationToCoolerRequest(string $cartId): array
    {
        $response = $this->client->request(
            'GET',
            self::API_URL.$this->storeHash.'/v3/carts/'.$cartId,
            ['headers' => self::BC_HEADERS]
        );

        if ($response->getStatusCode() !== 200) {
            throw new InvalidArgumentException('Something went wrong retrieving the Footprint');
        }

        $cart = json_decode($response->getBody()->getContents(), true);
        $requestData = [];
        $requestData['currency'] = $cart['data']['currency']['code'];

        foreach ($cart['data']['line_items']['physical_items'] as $item) {
            $requestData['itemsInfo'][] = [
                'product_id' => $item['sku'],
                'quantity' => $item['quantity'],
                'price' => $item['sale_price'],
            ];
        }
        foreach ($cart['data']['line_items']['digital_items'] as $item) {
            $requestData['itemsInfo'][] = [
                'product_id' => $item['sku'],
                'quantity' => $item['quantity'],
                'price' => $item['sale_price'],
            ];
        }

        return $requestData;
    }


    public function addCustomItem(CarbonItem $carbonItem)
    {
        $response = $this->client->request(
            'POST',
            self::API_URL . $this->storeHash . '/v3/carts/' . $carbonItem->getCartId() . '/items',
            [
                RequestOptions::JSON => [
                    'custom_items' => [[
                            'name' => $carbonItem->getName(),
                            'list_price' => $carbonItem->getCarbonPrice(),
                            'quantity' => $carbonItem->quantity(),
                            'sku' => $carbonItem->sku(),
                    ]]
                ],
                'headers' => self::BC_HEADERS
            ]
        );
        if ($response->getStatusCode() !== 200) {
            throw new InvalidArgumentException('Something went wrong updating the Order Message');
        }
    }

    /**
     * @param int $orderId
     * @param float $carbonPerDollar
     * @param float $totalCarbonCost
     * @return void
     * @throws GuzzleException
     */
    public function updateOrderCustomerMessage(int $orderId, float $carbonPerDollar, float $totalCarbonCost)
    {
        $response = $this->client->request(
            'PUT',
            self::API_URL.$this->storeHash.'/v2/orders/'.$orderId,
            [
                RequestOptions::JSON => [
                    'customer_message' => $totalCarbonCost.' of CO2 have been neutralized for a cost of '.$carbonPerDollar.'USD'
                ],
                'headers' => self::BC_HEADERS
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new InvalidArgumentException('Something went wrong updating the Order Message');
        }
    }
}
