<?php

use Symfony\Component\HttpFoundation\Request;

require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

// Our web handlers

$app->get('/', function() use($app) {
  $app['monolog']->addDebug('logging output.');
  return $app['twig']->render('index.twig');
});

$app->get('/test', function() use($app) {
	return "HI!";
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
