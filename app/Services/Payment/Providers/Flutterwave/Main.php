<?php
namespace App\Services\Payment\Providers\Flutterwave;

use App\Constants\Constants;
use App\Models\PaymentAccount;
use GuzzleHttp\ClientException;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use App\Models\Configuration;

use App\Services\API;

class Main extends API
{
    public function __construct()
    {
        $this->secret = config('payment.providers.flutterwave.secret_key');
    }

    public function baseUrl(): string
    {
        return 'https://api.flutterwave.com/';
    }

    public function getBanks(string $country_code): \stdClass
    {
        return $this->_get("v3/banks/{$country_code}");
    }

    public function getBankBranch(string $bank_id): \stdClass
    {
        return $this->_get("v3/banks/{$bank_id}/branches");
    }

    public function validateAccountNumber(string $account_number, string $bank_code): \stdClass
    {
        $data = [
            'account_number' => $account_number,
            'account_bank' => $bank_code,
        ];
        return $this->_post('v3/accounts/resolve', $data);
    }

    public function verifyTransaction(string $id): \stdClass
    {
        return $this->_get("v3/transactions/{$id}/verify");
    }

    public function transferFundsToRecipient(PaymentAccount $transferData, float $amount): \stdClass
    {
        $need_branch_code = ['GH', 'UG', 'TZ'];
        $naira_to_dollar = Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first();
        //TO DO: might want to implement a currency converter among providers
        $neededData = [
            'amount' => bcmul($amount, $naira_to_dollar->value, 0), //convert to Naira from dollars
            'account_number' => $transferData->identifier,
            'account_bank' => $transferData->bank_code,
            'currency' => 'NGN',
            'reference' => uniqid() . date('Ymd-His'),
        ];

        if (in_array($transferData->country_code, $need_branch_code)) {
            $neededData['destination_branch_code'] = $transferData->branch_code;
        }

        return $this->_post('v3/transfers', $neededData);
    }

    public function getTransferStatus(string $id): \stdClass
    {
        return $this->_get("v3/transfers/{$id}");
    }

    protected function setupStackHeaders($stack)
    {
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $this->secret);
            $request = $request->withHeader('Content-Type', 'application/json');
            return $request;
        }));

        return $stack;
    }
}
