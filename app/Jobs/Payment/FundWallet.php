<?php

namespace App\Jobs\Payment;

use App\Constants\Constants;
use App\Models\Payment;
use App\Models\WalletTransaction;
use App\Jobs\Users\NotifyTipping as NotifyTippingJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FundWallet implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private $user;
    private $wallet;
    private $provider;
    private $provider_id;
    private $flk;
    private $amount;
    private $fee;
    private $fund_type;
    private $funder_name;
    private $fund_note;
    private $originating_currency;
    private $originating_content_id;
    private $originating_client_source;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->user = $data['user'];
        $this->wallet = $data['wallet'];
        $this->provider = $data['provider'];
        $this->provider_id = $data['provider_id'];
        $this->flk = $data['flk'];
        $this->amount = $data['amount'];
        $this->fee = $data['fee'];
        $this->fund_type = array_key_exists('fund_type', $data) ? $data['fund_type'] : '';
        $this->funder_name = array_key_exists('funder_name', $data) ? $data['funder_name'] : '';
        $this->fund_note = array_key_exists('fund_note', $data) ? $data['fund_note'] : '';
        $this->originating_currency = array_key_exists('originating_currency', $data) ? $data['originating_currency'] : '';
        $this->originating_content_id = array_key_exists('originating_content_id', $data) ? $data['originating_content_id'] : null;
        $this->originating_client_source = array_key_exists('originating_client_source', $data) ? $data['originating_client_source'] : '';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            $payment = Payment::where('provider', $this->provider)->where('provider_id', $this->provider_id)->first();
            if (! is_null($payment)) {
                $data = [
                    'provider' => $this->provider,
                    'provider_id' => $this->provider_id,
                    'user_id' => $this->user->id,
                    'amount_in_flk' => $this->flk,
                    'amount_in_dollars' => $this->amount,
                    'payment_id' => $payment->id,
                ];
                $data_in_json = json_encode($data);

                throw new \Exception("Duplicate payment details provided with details: \n {$data_in_json}");
            }
            $newWalletBalance = bcadd($this->wallet->balance, $this->flk, 2);
            $walletTransaction = WalletTransaction::create([
                'public_id' => uniqid(rand()),
                'wallet_id' => $this->wallet->id,
                'amount' => $this->flk,
                'balance' => $newWalletBalance,
                'transaction_type' => 'fund',
                'details' => 'Fund wallet with ' . $this->flk . ' FLK via ' . $this->provider,
            ]);

            $this->wallet->balance = $newWalletBalance;
            $this->wallet->save();

            $walletTransaction->payments()->create([
                'payer_id' => $this->user->id,
                'payee_id' => $this->user->id,
                'amount' => $this->amount,
                'payment_processor_fee' => $this->fee,
                'provider' => $this->provider,
                'provider_id' => $this->provider_id,
            ]);

            $amount_in_dollars = bcdiv($this->flk, 100, 6);

            $platform_charge = Constants::TIPPING_CHARGE;
            if ($this->user->user_charge_type === 'non-profit') {
                $platform_charge = Constants::TIPPING_CHARGE;
            }
            $platform_share = bcmul($amount_in_dollars, $platform_charge, 6);
            $creator_share = bcmul($amount_in_dollars, 1 - $platform_charge, 6);
            $revenue = $this->user->revenues()->create([
                'revenueable_type' => 'user',
                'revenueable_id' => $this->user->id,
                'amount' => $amount_in_dollars,
                'payment_processor_fee' => 0,
                'platform_share' => $platform_share,
                'benefactor_share' => $creator_share,
                'referral_bonus' => 0,
                'revenue_from' => 'tip',
                'added_to_payout' => 1,
            ]);

            if ( ! is_null($this->originating_currency)) {
                $revenue->originating_currency = $this->originating_currency;
            }

            if ( ! is_null($this->originating_content_id)) {
                $revenue->originating_content_id = $this->originating_content_id;
            }

            if ( ! is_null($this->originating_client_source)) {
                $revenue->originating_client_source = $this->originating_client_source;
            }

            $revenue->save();

            if (! is_null($revenue->originating_content_id)) {
                $content_tip_count = $this->user->revenues()->where('originating_content_id', $revenue->originating_content_id)->where('revenue_from', 'tip')->count();
                $websocket_client = new \WebSocket\Client(config('services.websocket.url'));
                $websocket_client->text(json_encode([
                    'event' => 'app-update-number-of-tips-for-content',
                    'source_type' => 'app',
                    'content_id' => $revenue->originating_content_id,
                    'tips_count' => $content_tip_count
                ]));
                $websocket_client->close();
            }
            
            DB::commit();
            if ($this->fund_type == 'tip') {
                $custom_message = "You just got a gift of {$this->flk} Flok Cowries from an anonymous person";
                if ($this->funder_name != "" && $this->funder_name != null) {
                    $custom_message = "You just got a gift of {$this->flk} Flok Cowries from {$this->funder_name}";
                    if ($this->fund_note != "" && $this->fund_note != null) {
                        $custom_message = "You just got a gift of {$this->flk} Flok Cowries from {$this->funder_name} with the note '{$this->fund_note}'";
                    }
                }
                
                NotifyTippingJob::dispatch([
                    'tipper' => $this->user,
                    'tippee' => $this->user,
                    'amount_in_flk' => $this->flk,
                    'wallet_transaction' => $walletTransaction,
                    'custom_message' => $custom_message
                ]);
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            throw $exception;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
