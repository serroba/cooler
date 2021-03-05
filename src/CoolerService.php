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
     * @return string
     * @throws GuzzleException
     */
    public function retrieveFootprint(string $currency, array $items): string
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
                $body = json_decode($response->getBody()->getContents(), true);
                return $body['id'];
            }
        }

        throw new InvalidArgumentException('Something went wrong retrieving the Footprint');
    }

    /**
     * @param array $transactionIds
     * @return array
     * @throws GuzzleException
     */
    public function neutralizeTransactions(array $transactionIds): array
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

        $body = json_decode($response->getBody()->getContents(), true);

        $info = [];

        foreach ($body['transactions']['items'] as $item) {
            if ($item['id'] === $transactionIds[0]) {
                $info['carbonPerDollar'] = $item['footprint']['carbon_cost'];
                $info['totalCarbonCost'] = $item['footprint']['carbon_per_dollar'];
            }
        }

        return $info;
    }
}
