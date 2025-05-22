<?php

namespace App\Services;

use App\Contracts\PaymentInterface;
use App\Services\PayPalService;
use App\Services\StripeService;

class PaymentManager
{
    protected PaymentInterface $driver;

    public function __construct(string $method = 'stripe')
    {
        $this->driver = match ($method) {
            'paypal' => new PayPalService(),
            'stripe', default => new StripeService(),
        };
    }

    public function driver(): PaymentInterface
    {
        return $this->driver;
    }
}
