<?php
namespace App\Services\Payment\Providers\ApplePay;

use App\Constants\Constants;
use App\Models\PaymentAccount;
use Illuminate\Support\Facades\Cache;


class Test
{
    protected $base_url;

    public function __construct()
    {
        $this->base_url = 'https://sandbox.itunes.apple.com/';
    }

    public function verifyTransaction(string $reference): \stdClass
    {
        $key = "{$this->base_url}verifyReceipt";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }
}