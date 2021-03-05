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

$app[Client::class] = function (Application $app) {
    return new Client();
};
$app[BCService::class] = function (Application $app) {
    $clientId = getenv('BC_CLIENT_ID') ?: 'cdvg04j6qg6wqyrv07tlszt6uyzu5ia';
    $token = getenv('BC_TOKEN') ?: '91z9nkuqu279vxs8kh1aldpbpq7w0hf';
    return new BCService($app[Client::class], $clientId, $token);
};

$app[CoolerService::class] = function (Application $app) {
    return new CoolerService($app[Client::class]);
};

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

$app->post('/webhooks/cart_updated', function(Request $request) use($app) {
    $storeHash = RequestExtractor::extractStoreHash($request);
    $cartId = RequestExtractor::extractCartId($request);

    /** @var BCService $bcService */
    $bcService = $app[BCService::class];
    $bcService->setHash($storeHash);
    $cartInfo = $bcService->mapCartInformationToCoolerRequest($cartId);

    /** @var CoolerService $coolerService */
    $coolerService = $app[CoolerService::class];
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

    /** @var BCService $bcService */
    $bcService = $app[BCService::class];
    $bcService->setHash($storeHash);
    $currency = $bcService->retrieveOrderCurrency($orderId);
    $itemsInfo = $bcService->retrieveOrderProductsInfo($orderId);

    /** @var CoolerService $coolerService */
    $coolerService = $app[CoolerService::class];
    $transactionId = $coolerService->retrieveTransactionId($currency, $itemsInfo);
    $coolerService->neutralizeTransactions([$transactionId]);
    $transactionInfo = $coolerService->retrieveTransactionInfo($transactionId);

    $bcService->updateOrderCustomerMessage($orderId, $transactionInfo['carbonPerDollar'], $transactionInfo['totalCarbonCost']);

    return $transactionId;
});


$app->get('/config', function (Request $request) {
    return json_encode([
        'client_id' => getenv('BC_CLIENT_ID'),
        'token' => getenv('BC_TOKEN'),
    ]);
});

$app->run();
