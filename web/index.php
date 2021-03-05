<?php

use Cooler\BCService;
use Cooler\CarbonItem;
use Cooler\CoolerService;
use Cooler\RequestExtractor;
use GuzzleHttp\Client;
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
    $cartInfo = $bcService->mapCartInformationToCoolerRequest($cartId);

    $coolerService = new CoolerService();
    $footPrint = $coolerService->retrieveFootprint($cartInfo['currency'], $cartInfo['itemsInfo']);

    $totalPriceCarbon = 0;
    $totalCarbonAmount = 0;

    foreach ($footPrint['items'] as $item) {
        $totalPriceCarbon += $item['footprint']['carbon_per_dollar'];
        $totalCarbonAmount += $item['footprint']['carbon_cost'];
    }
    if (!isset($cartInfo['custom_item_id'])) {
        $bcService->addCustomItem(new CarbonItem($cartId, $totalPriceCarbon, $totalCarbonAmount));
    } else {
//        $bcService->updateCustomItem(new CarbonItem($cartId, $totalPriceCarbon, $totalCarbonAmount, $cartInfo['custom_item_id']));
    }

	return json_encode($footPrint);
});

$app->post('/webhooks', function(Request $request) use($app) {
    $storeHash = RequestExtractor::extractStoreHash($request);
    $orderId = RequestExtractor::extractOrderId($request);

    $bcService = new BCService($storeHash);
    $currency = $bcService->retrieveOrderCurrency($orderId);
    $itemsInfo = $bcService->retrieveOrderProductsInfo($orderId);

    $coolerService = new CoolerService();
    $transactionId = $coolerService->retrieveTransactionId($currency, $itemsInfo);
    $coolerService->neutralizeTransactions([$transactionId]);
    $transactionInfo = $coolerService->retrieveTransactionInfo($transactionId);

    $bcService->updateOrderCustomerMessage($orderId, $transactionInfo['carbonPerDollar'], $transactionInfo['totalCarbonCost']);

    return $transactionId;
});


$app->run();
