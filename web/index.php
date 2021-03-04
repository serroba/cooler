<?php

use Cooler\BCService;
use Cooler\CoolerService;
use Cooler\StoreHashExtractor;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application();
$app['debug'] = true;

const COOLER_HOST = 'https://api-staging.cooler.dev/v1/';


// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

$app['bc_headers'] = [
    'X-Auth-Client' => 'cdvg04j6qg6wqyrv07tlszt6uyzu5ia',
    'X-Auth-Token' => '4glcdmnprqzbomc0antzo0eknlkrpr4',
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
];
$app['cooler_headers'] = [
    'COOLER-API-KEY' => 'cooler_9005044e-e589-440f-be51-9c3cd3828dc3',
    'Content-Type' => 'application/json',
];

$app->post('/webhooks/cart_updated', function(Request $request) use($app) {

    $client = new GuzzleHttp\Client();
    $storeHash = StoreHashExtractor::extract($request);
    $body = json_decode($request->getContent(), true);
    if (empty($body) || !$body['scope'] === 'store/cart/updated') {
        return 'wrong scope :( !';
    }
    $cartId = $body['data']['id'];
    $response = $client->request(
        'GET',
        'https://api.bigcommerce.com/stores/'.$storeHash.'/v3/carts/'.$cartId,
        ['headers' => $app['bc_headers']]
    );
    $cart = json_decode($response->getBody()->getContents(), true);
    $currency = $cart['data']['currency']['code'];
    $items = [];
    foreach ($cart['data']['line_items']['physical_items'] as $item) {
        $items[] = [
            'product_id' => $item['sku'],
            'quantity' => $item['quantity'],
            'price' => $item['sale_price'],
        ];
    }
    foreach ($cart['data']['line_items']['digital_items'] as $item) {
        $items[] = [
            'product_id' => $item['sku'],
            'quantity' => $item['quantity'],
            'price' => $item['sale_price'],
        ];
    }
    $response = $client->request('POST', 'https://api-staging.cooler.dev/v1/footprint/products', [
        GuzzleHttp\RequestOptions::JSON => [
            'currency' => $currency,
            'items' => $items
        ],
        'headers' => $app['cooler_headers']
    ]);
    $t = json_decode($response->getBody()->getContents(), true);

	return json_encode($t);
});

$app->post('/webhooks', function(Request $request) use($app) {
    $storeHash = StoreHashExtractor::extract($request);
    $body = json_decode($request->getContent(), true);

    if (empty($body) || !$body['scope'] === 'store/cart/converted') {
        return 'wrong scope';
    }

    $orderId = $body['data']['orderId'];

    $bcService = new BCService($storeHash);
    $currency = $bcService->retrieveOrderCurrency($orderId);
    $itemsInfo = $bcService->retrieveOrderProductsInfo($orderId);

    $coolerService = new CoolerService();
    $transactionId = $coolerService->retrieveFootprint($currency, $itemsInfo);
    $coolerService->neutralizeTransactions([$transactionId]);

    return 'success!';
});


$app->run();
