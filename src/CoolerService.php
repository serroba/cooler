<?php

declare(strict_types=1);

namespace Cooler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;

class CoolerService
{
    const API_URL = 'https://api-staging.cooler.dev/v1/footprint/';
    const COOLER_HEADERS = [
        'COOLER-API-KEY' => 'cooler_9005044e-e589-440f-be51-9c3cd3828dc3',
        'Content-Type' => 'application/json',
    ];

    private $client = null;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param string $currency
     * @param array $items
     * @return array
     * @throws GuzzleException
     */
    public function retrieveFootprint(string $currency, array $items): array
    {
        $response = $this->client->request('POST', self::API_URL.'products', [
            RequestOptions::JSON => [
                'currency' => $currency,
                'items' => $items
            ],
            'headers' => self::COOLER_HEADERS
        ]);

        if ($response->getStatusCode() === 201) {
            if (empty($body) || !isset($body['producer'])) {
                return json_decode($response->getBody()->getContents(), true);
            }
        }

        throw new InvalidArgumentException('Something went wrong retrieving the Footprint');
    }

    public function retrieveTransactionId(string $currency, array $items)
    {
        return $this->retrieveFootprint($currency, $items)['id'];
    }

    /**
     * @param array $transactionIds
     * @return void
     * @throws GuzzleException
     */
    public function neutralizeTransactions(array $transactionIds): void
    {
        $response = $this->client->request('POST', self::API_URL.'neutralize/transactions', [
            RequestOptions::JSON => [
                'ids' => $transactionIds
            ],
            'headers' => self::COOLER_HEADERS
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new InvalidArgumentException('Something went wrong neutralizing the Transactions');
        }
    }

    /**
     * @param string $transactionId
     * @return array
     * @throws GuzzleException
     */
    public function retrieveTransactionInfo(string $transactionId): array
    {
        $response = $this->client->request('GET', 'https://api-staging.cooler.dev/v1/transactions/'.$transactionId, [
            'headers' => self::COOLER_HEADERS
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new InvalidArgumentException('Something went wrong neutralizing the Transactions');
        }

        $body = json_decode($response->getBody()->getContents(), true);

        $info = [];
        $info['totalCarbonCost'] = $body['total_carbon_cost'];
        $info['carbonPerDollar'] = $body['items'][0]['footprint']['carbon_per_dollar'];

        return $info;
    }
}
