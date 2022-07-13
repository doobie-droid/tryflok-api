<?php

namespace App\Services\Payment\Providers\Stripe;

use App\Models\PaymentAccount;
use App\Services\Payment\Providers\Stripe\Main;
use App\Services\Payment\Providers\Stripe\Test;

class Stripe
{
    protected $driver;

    public function __construct()
    {
        if (config('app.env') == 'testing') {
            $this->driver = new Test;
        } else {
            $this->driver = new Main;
        }
    }

    public function verifyTransaction(string $id): \stdClass
    {
        return $this->driver->verifyTransaction($id);
    }

    public function transferFundsToRecipient(PaymentAccount $transferData, float $amount): \stdClass
    {
        return $this->driver->transferFundsToRecipient($transferData, $amount);
    }

    public function chargeViaToken(int $amount, string $token): \stdClass
    {
        return $this->driver->chargeViaToken($amount, $token);
    }

    public function getTransferStatus(string $id): \stdClass
    {
        return $this->driver->getTransferStatus($id);
    }
}
