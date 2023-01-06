<?php
namespace App\Services\Payment\Providers\Stripe;

use App\Models\PaymentAccount;
use Illuminate\Support\Facades\Cache;

class Test
{
    protected $base_url;

    public function __construct()
    {
        $this->base_url = 'https://api.stripe.com/';
    }

    public function verifyTransaction(string $reference): \stdClass
    {
        $key = "{$this->base_url}v1/charges/{$reference}";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }

    public function transferFundsToRecipient(PaymentAccount $transferData, float $amount): \stdClass
    {
        $key = "{$this->base_url}v1/transfers";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }

    public function chargeViaToken(int $amount, string $token): \stdClass
    {
        $key = "{$this->base_url}v1/charges";

        $response = json_decode((string) Cache::get($key), true);
        
        return response()->json($response)->getData();
    }

    public function getTransferStatus(string $id): \stdClass
    {
        $key = "{$this->base_url}v1/transfers/{$id}";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }

    public function createCustomer(string $source, string $email): \stdClass
    {
        $key = "{$this->base_url}v1/customers";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }

    public function createCharge(int $amount, string $currency, string $customer_id): \stdClass
    {
        $key = "{$this->base_url}v1/charges";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }
}