<?php

use Cooler\BCService;
use Cooler\CoolerService;
use Cooler\RequestExtractor;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

$app->post('/webhooks/cart_updated', function(Request $request) use($app) {
    $storeHash = RequestExtractor::extractStoreHash($request);
    $cartId = RequestExtractor::extractCartId($request);

    $bcService = new BCService($storeHash);
    $cartInfo = $bcService->retrieveCartInfo($cartId);

    $coolerService = new CoolerService();
    $transactionId = $coolerService->retrieveFootprint($cartInfo['currency'], $cartInfo['itemsInfo']);

	return json_encode($transactionId);
});

$app->post('/webhooks', function(Request $request) use($app) {
    $storeHash = RequestExtractor::extractStoreHash($request);
    $orderId = RequestExtractor::extractOrderId($request);

    $bcService = new BCService($storeHash);
    $currency = $bcService->retrieveOrderCurrency($orderId);
    $itemsInfo = $bcService->retrieveOrderProductsInfo($orderId);

    $coolerService = new CoolerService();
    $transactionId = $coolerService->retrieveFootprint($currency, $itemsInfo);
    $coolerService->neutralizeTransactions([$transactionId]);

    return $transactionId;
});


$app->run();
