<?php

namespace App\Services\Payment\Providers\ApplePay;

use App\Models\PaymentAccount;
use App\Services\Payment\Providers\ApplePay\Main;
use App\Services\Payment\Providers\ApplePay\Test;

class ApplePay
{
    protected $driver;

    public const PRODUCT_TO_AMOUNT = [
        '250_flc' => 3,
        '500_flc' => 6,
        '1000_flc' => 12,
        '3000_flc' => 36,
        '5000_flc' => 60,
        '10000_flc' => 120,
    ];

    public const PRODUCT_TO_FLC = [
        '250_flc' => 250,
        '500_flc' => 500,
        '1000_flc' => 1000,
        '3000_flc' => 3000,
        '5000_flc' => 5000,
        '10000_flc' => 10000,
    ];

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
}
