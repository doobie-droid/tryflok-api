<?php
namespace App\Services\Payment\Providers\Stripe;

use App\Models\PaymentAccount;
use App\Services\API;

class Main extends API
{
    protected $secret;

    protected $perPage;

    public function __construct()
    {
        $this->secret = config('payment.providers.stripe.secret_key');
    }

    public function baseUrl(): string
    {
        return 'https://api.stripe.com/';
    }

    public function verifyTransaction(string $id): \stdClass
    {
        return $this->_get("v1/charges/{$id}");
    }

    public function transferFundsToRecipient(PaymentAccount $transferData, float $amount): \stdClass
    {
        //note that the amount supplied here is in dollars and must be converted to cents
        $amount_in_cents = bcmul($amount, 100, 0);
        $response = $this->_post('v1/transfers', [
        'amount' => $amount_in_cents,
        'currency' => 'usd',
        'destination' => $transferData->identifier,
        ]);
        return $response;
    }

    public function chargeViaToken(int $amount, string $token): \stdClass
    {
        //note that the amount provided here is in cents
        return $this->_post('v1/charges', [
            'amount' => (int) $amount,
            'currency' => 'usd',
            'source' => $token,
        ]);
    }

    public function getTransferStatus(string $id): \stdClass
    {
        return $this->_get("v1/transfers/{$id}");
    }

    public function execute($httpMethod, $url, array $parameters = [])
    {
        try {
            $results = $this->getClient()->{$httpMethod}($url, ['form_params' => $parameters]);
            $res  = json_decode((string) $results->getBody(), true);
            return response()->json($res)->getData();
        } catch (ClientException $exception) {
            return response()->json([
               'status' => false,
               'status_code' => $exception->getCode(),
               'message' => $exception->getMessage(),
            ])->getData();
        }
    }

    private function setupStackHeaders($stack)
    {
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $this->secret);
            $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
            return $request;
        }));

        return $stack;
    }

}