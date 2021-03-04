<?php

declare(strict_types=1);

namespace Cooler;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class RequestExtractor
{
    /**
     * @param Request $request
     * @return string
     */
    public static function extractStoreHash(Request $request): string
    {
        $body = json_decode($request->getContent(), true);
        if (empty($body) || !isset($body['producer'])) {
            throw new InvalidArgumentException('wrong producer :( !');
        }
        return str_replace('stores/', '', $body['producer']);
    }

    /**
     * @param Request $request
     * @return string
     */
    public static function extractCartId(Request $request): string
    {
        $body = json_decode($request->getContent(), true);
        if (empty($body) || !$body['scope'] === 'store/cart/updated') {
            throw new InvalidArgumentException('wrong producer :( !');
        }
        return $body['data']['id'];
    }

    /**
     * @param Request $request
     * @return int
     */
    public static function extractOrderId(Request $request): int
    {
        $body = json_decode($request->getContent(), true);
        if (empty($body) || !$body['scope'] === 'store/cart/updated') {
            throw new InvalidArgumentException('wrong producer :( !');
        }
        return $body['data']['orderId'];
    }
}
