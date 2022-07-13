<?php
namespace App\Services\Payment\Providers\ApplePay;

use App\Constants\Constants;
use App\Models\PaymentAccount;

use App\Services\API;

class Main extends API
{
    protected $secret;

    protected $perPage;

    public function __construct()
    {
        $this->secret = config('payment.providers.apple.secret_key');
    }

    public function baseUrl(): string
    {
        return config('payment.providers.apple.api_url');
    }

    public function verifyTransaction(string $reference): \stdClass
    {
        return $this->_post('verifyReceipt', [
            'receipt-data' => $reference,
            'password' => $this->secret,
        ]);
    }

    private function setupStackHeaders($stack)
    {
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $request = $request->withHeader('Content-Type', 'application/json');
            return $request;
        }));

        return $stack;
    }

}