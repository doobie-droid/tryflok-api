<?php
namespace App\Services\Payment\Providers\Flutterwave;

use App\Constants\Constants;
use App\Models\PaymentAccount;
use Illuminate\Support\Facades\Cache;


class Test
{
    protected $base_url;

    public function __construct()
    {
        $this->base_url = 'https://api.flutterwave.com/';
    }

    public function getBanks(string $country_code): \stdClass
    {
        $key = "{$this->base_url}v3/banks/{$country_code}";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }

    public function getBankBranch(string $bank_id): \stdClass
    {
        $key = "{$this->base_url}v3/banks/{$bank_id}/branches";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }

    public function validateAccountNumber(string $account_number, string $bank_code): \stdClass
    {
        $key = "{$this->base_url}v3/accounts/resolve";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }

    public function recurrentTipCharge(string $card_token, string $email, string $tx_ref, string $amount): \stdClass
    {
        $key = "{$this->base_url}v3/tokenized-charges";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }

    public function verifyTransaction(string $id): \stdClass
    {
        $key = "{$this->base_url}v3/transactions/{$id}/verify";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }

    public function transferFundsToRecipient(PaymentAccount $transferData, float $amount): \stdClass
    {
        $key = "{$this->base_url}v3/transfers";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }

    public function getTransferStatus(string $id): \stdClass
    {
        $key = "{$this->base_url}v3/transfers/{$id}";

        $response = json_decode((string) Cache::get($key), true);

        return response()->json($response)->getData();
    }

}
