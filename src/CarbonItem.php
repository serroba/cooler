<?php

declare(strict_types=1);

namespace Cooler;

class CarbonItem
{
    private string $cartId;
    private float $priceCarbon;
    private float $carbonAmount;

    public function __construct(string $cartId, float $priceCarbon, float $carbonAmount)
    {
        $this->cartId = $cartId;
        $this->priceCarbon = $priceCarbon;
        $this->carbonAmount = round($carbonAmount, 2);
    }

    /**
     * @return string
     */
    public function getCartId(): string
    {
        return $this->cartId;
    }

    /**
     * @return float
     */
    public function getCarbonPrice(): float
    {
        return $this->priceCarbon;
    }

    public function getName(): string
    {
        return 'To offset ' . $this->carbonAmount . 'kg you pay $' . $this->priceCarbon;
    }

    public function sku(): string
    {
        return 'custom-sku-custom';
    }

    public function quantity(): int
    {
        return 1;
    }
}