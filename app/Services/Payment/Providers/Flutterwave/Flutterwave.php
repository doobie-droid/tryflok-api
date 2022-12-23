<?php

namespace App\Services\Payment\Providers\Flutterwave;

use App\Constants\Constants;
use App\Models\PaymentAccount;
use App\Services\Payment\PaymentInterface;
use App\Services\Payment\Providers\Flutterwave\API;
use Illuminate\Contracts\Foundation\App;
use  App\Services\Payment\Providers\Flutterwave\Main;
use  App\Services\Payment\Providers\Flutterwave\Test;

class Flutterwave
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
 
    public function getBanks(string $country_code): \stdClass
    {
        return $this->driver->getBanks($country_code);
    }

    public function getBankBranch(string $bank_id): \stdClass
    {
        return $this->driver->getBankBranch($bank_id);
    }

    public function validateAccountNumber(string $account_number, string $bank_code): \stdClass
    {
        return $this->driver->validateAccountNumber($account_number, $bank_code);
    }

    public function recurrentTipCharge(string $card_token, string $email, string $tx_ref, string $amount): \stdClass
    {
        return $this->driver->recurrentTipCharge($card_token, $email, $tx_ref, $amount);
    }

    public function verifyTransaction(string $id): \stdClass
    {
        return $this->driver->verifyTransaction($id);
    }

    public function transferFundsToRecipient(PaymentAccount $transferData, float $amount): \stdClass
    {
        return $this->driver->transferFundsToRecipient($transferData, $amount);
    }

    public function getTransferStatus(string $id): \stdClass
    {
        return $this->driver->getTransferStatus($id);
    }
}
