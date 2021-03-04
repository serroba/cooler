<?php

use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;


// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

$app['cooler.key'] = 'cooler_9005044e-e589-440f-be51-9c3cd3828dc3';

$app->post('/webhooks/cart_updated', function() use($app) {
	return $app['cooler.key'];
});

$app->post('/webhooks', function(Request $request) use($app) {
    $client = new GuzzleHttp\Client();
    $client->request('POST', 'https://webhook.site/3126fcc2-fe6f-4518-bd00-8c5c8e09ad36', [
        GuzzleHttp\RequestOptions::JSON => [$request->get('test')]]
    );

    return '';

    // if (!$request->get('scope') === 'store/cart/converted') {
    //     return;
    // }
    //
    // $response = $client->request('POST', 'https://api-staging.cooler.dev/v1/footprint/products', [
    //     GuzzleHttp\RequestOptions::JSON => [
    //         'currency' => 'USD',
    //         'items' => [
    //             [
    //                 'product_id' => 'B2-R',
    //                 'quantity' => 1,
    //                 'price' => 599
    //             ]
    //         ]
    //     ]
    // ]);
    //
    // if ($response->getStatusCode() === 200) {
    //     $body = json_decode($response->getBody());
    //     $neutralisationId = $body['id'];
    //
    //     $response = $client->request('POST', 'https://api-staging.cooler.dev/v1/footprint/products', [
    //         GuzzleHttp\RequestOptions::JSON => [
    //             'transactions' => [$neutralisationId]
    //         ]
    //     ]);
    //
    //     if ($response->getStatusCode() === 200) {
    //         return 'success!';
    //     }
    // }
    //
    // return 'failed :(';
});


$app->run();
