<?php

declare(strict_types=1);

namespace Cooler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;

class BCService
{
    private const API_URL = 'https://api.bigcommerce.com/stores/';
    private $bcHeaders = [];
    private const INCLUDES = [
        'line_items.physical_items.options'
    ];

    private $storeHash = '';
    private $client;

    /**
     * @param Client $client
     * @param string $clientId
     * @param string $token
     */
    public function __construct(Client $client, string $clientId, string $token)
    {
        $this->bcHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Auth-Client' => $clientId,
            'X-Auth-Token' => $token,
        ];
        $this->client = new Client();
    }

    /**
     * @param string $storeHash
     */
    public function setHash(string $storeHash)
    {
        $this->storeHash = $storeHash;
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
            ['headers' => $this->bcHeaders]
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
            ['headers' => $this->bcHeaders]
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
            self::API_URL . $this->storeHash . '/v3/carts/' . $cartId . '?' .
            http_build_query(['includes' => implode(',', self::INCLUDES)]),
            ['headers' => $this->bcHeaders]
        );

        if ($response->getStatusCode() !== 200) {
            throw new InvalidArgumentException('Something went wrong retrieving the Footprint');
        }

        $cart = json_decode($response->getBody()->getContents(), true);
        $requestData = [];
        $requestData['currency'] = $cart['data']['currency']['code'];

        foreach ($cart['data']['line_items']['physical_items'] as $item) {
            if (isset($item['options'])) {
                foreach ($item['options'] as $option) {
                    if ($option['name'] === 'neutralize carbon?' && $option['value'] === 'Yes') {
                        $requestData['itemsInfo'][] = [
                            'product_id' => $item['sku'],
                            'quantity' => $item['quantity'],
                            'price' => $item['sale_price'],
                        ];
                        break;
                    }
                }
            } else {
                $requestData['itemsInfo'][] = [
                    'product_id' => $item['sku'],
                    'quantity' => $item['quantity'],
                    'price' => $item['sale_price'],
                ];
            }
        }
        foreach ($cart['data']['line_items']['digital_items'] as $item) {
            $requestData['itemsInfo'][] = [
                'product_id' => $item['sku'],
                'quantity' => $item['quantity'],
                'price' => $item['sale_price'],
            ];
        }
        foreach ($cart['data']['line_items']['custom_items'] as $item) {
            if ($item['sku'] === 'cooler-custom-sku') {
                $requestData['custom_item_id'] = $item['id'];
            }
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
                'headers' => $this->bcHeaders
            ]
        );
        if ($response->getStatusCode() >= 300) {
            throw new InvalidArgumentException('Something went wrong Adding a custom Item');
        }
    }

    public function updateCustomItem(CarbonItem $carbonItem)
    {
        $response = $this->client->request(
            'PUT',
            self::API_URL . $this->storeHash . '/v3/carts/' . $carbonItem->getCartId() . '/items/' . $carbonItem->getLineItemId(),
            [
                RequestOptions::JSON => [
                    'line_item' => [
                        'name' => $carbonItem->getName(),
                        'list_price' => $carbonItem->getCarbonPrice(),
                        'quantity' => $carbonItem->quantity(),
                        'sku' => $carbonItem->sku(),
                    ]
                ],
                'headers' => $this->bcHeaders
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
                'headers' => $this->bcHeaders
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new InvalidArgumentException('Something went wrong updating the Order Message');
        }
    }
}
