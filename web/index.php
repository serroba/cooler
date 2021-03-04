<?php

use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

const COOLER_HOST = 'https://api-staging.cooler.dev/v1/';


// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

$app['cooler.key'] = 'cooler_9005044e-e589-440f-be51-9c3cd3828dc3';


$app->get('/webhooks/cart_updated', function() use($app) {
    return $app['cooler.key'];
});

$app->post('/webhooks/cart_updated', function(Request $request) use($app) {

	return $request->getContent();
});

$app->post('/webhooks', function(Request $request) use($app) {
    $client = new GuzzleHttp\Client();
    $storeHash = 'tb0i4pdxam';
    $headers = array('X-Auth-Client' => 'cdvg04j6qg6wqyrv07tlszt6uyzu5ia', 'X-Auth-Token' => 'id0alsbs1c74qsymo8sjrqhy2r4zaio');
    $body = json_decode($request->getContent(), true);

    if (empty($body) || !$body['scope'] === 'store/cart/converted') {
        return 'wrong scope';
    }

    $orderId = $body['data']['orderId'];

    // Retrieve Order's currency
    $response = $client->request(
'GET',
    'https://api.bigcommerce.com/stores/'.$storeHash.'/v2/orders/'.$orderId,
        ['headers' => $headers]
    );
    $body = json_decode($response->getBody());
    $currency = $body['currency_code'];

    // Retrieve Orders' items info
    $response = $client->request(
        'POST',
        'https://api.bigcommerce.com/stores/'.$storeHash.'/v2/orders/'.$orderId.'/products',
        ['headers' => $headers]
    );
    $body = json_decode($response->getBody());
    $items = [];

    foreach ($body as $product) {
        $items[] = [
            'product_id' => $product['product_id'],
            'quantity' => $product['quantity'],
            'price' => $product['total_inc_tax']
        ];
    }

    $response = $client->request('POST', 'https://api-staging.cooler.dev/v1/footprint/products', [
        GuzzleHttp\RequestOptions::JSON => [
            'currency' => $currency,
            'items' => $items
        ]
    ]);

    if ($response->getStatusCode() === 200) {
        $body = json_decode($response->getBody());
        $neutralisationId = $body['id'];

        $response = $client->request('POST', 'https://api-staging.cooler.dev/v1/footprint/products', [
            GuzzleHttp\RequestOptions::JSON => [
                'transactions' => [$neutralisationId]
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            return 'success!';
        }
    }

    return 'failed :(';
});


$app->run();
