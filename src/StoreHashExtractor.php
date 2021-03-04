<?php

declare(strict_types=1);

namespace Cooler;

use Symfony\Component\HttpFoundation\Request;

class StoreHashExtractor
{
    public static function extract(Request $request): string
    {
        $body = json_decode($request->getContent(), true);
        if (empty($body) || !isset($body['producer'])) {
            throw new \InvalidArgumentException('wrong producer :( !');
        }
        return str_replace('stores/', '', $body['producer']);
    }
}
