<?php

namespace App\Jobs\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Constants\Constants;
use App\Models\UserTip;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Models\ExternalCommunity;
use Illuminate\Support\Facades\DB;
use App\Jobs\Users\NotifyTipping as NotifyTippingJob;

class AnonymousUserTip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();
        try{
            $userToTip = User::where('id', $this->data['tippee_id'])->first();
            $amount_in_dollars = bcdiv($this->data['flk'], 100, 6);
            $platform_charge = Constants::TIPPING_CHARGE;
            if ($userToTip->user_charge_type === 'non-profit') {
                $platform_charge = Constants::TIPPING_CHARGE;
            }
            $platform_share = bcmul($amount_in_dollars, $platform_charge, 6);
            $creator_share = bcmul($amount_in_dollars, 1 - $platform_charge, 6);

            $originating_currency = '';
            $originating_content_id = '';
            $originating_client_source = '';
            $revenue = $userToTip->revenues()->create([
                'revenueable_type' => 'user',
                'revenueable_id' => $userToTip->id,
                'amount' => $amount_in_dollars,
                'payment_processor_fee' => 0,
                'platform_share' => $platform_share,
                'benefactor_share' => $creator_share,
                'referral_bonus' => 0,
                'revenue_from' => 'tip',
                'added_to_payout' => 1,
            ]);
            if ( ! is_null($this->data['originating_currency'])) {
                $originating_currency = $this->data['originating_currency'];
                $revenue->originating_currency = $originating_currency;
            }

            if ( ! is_null($this->data['originating_content_id'])) {
                $originating_content_id = $this->data['originating_content_id'];
                $revenue->originating_content_id = $originating_content_id;
            }

            if ( ! is_null($this->data['originating_client_source'])) {
                $originating_client_source = $this->data['originating_client_source'];
                $revenue->originating_client_source = $originating_client_source;
            }

            $revenue->save();

            if (! is_null($revenue->originating_content_id)) {
                $content_tip_count = $userToTip->revenues()->where('originating_content_id', $revenue->originating_content_id)->where('revenue_from', 'tip')->count();
                $websocket_client = new \WebSocket\Client(config('services.websocket.url'));
                $websocket_client->text(json_encode([
                    'event' => 'app-update-number-of-tips-for-content',
                    'source_type' => 'app',
                    'content_id' => $revenue->originating_content_id,
                    'tips_count' => $content_tip_count
                ]));
                $websocket_client->close();
            }

            $creator_share_in_flk = $creator_share * 100;
            $newWalletBalance = bcadd($userToTip->wallet->balance, $creator_share_in_flk, 2);
            $transaction = WalletTransaction::create([
                'wallet_id' => $userToTip->wallet->id,
                'amount' => $creator_share_in_flk,
                'balance' => $newWalletBalance,
                'transaction_type' => 'fund',
                'details' => "@{$this->data['email']} gifted you {$creator_share_in_flk} Flok Cowries",
            ]);

            //record payment on payment table
            $transaction->payments()->create([
                'payee_id' => $userToTip->id,
                'amount' => $amount_in_dollars,
                'payment_processor_fee' => $this->data['fee'],
                'provider' => $this->data['provider'],
                'provider_id' => $this->data['provider_id'],
                'payer_email' => $this->data['email']
            ]);
            $userToTip->wallet->balance = $newWalletBalance;
            $userToTip->wallet->save();
            DB::commit();

            $customer_id = '';
            $card_token = '';
            
            if ($this->data['provider'] === 'flutterwave')
            {
                $provider = 'flutterwave';
                $card_token = $this->data['card_token'];
            }

            if ($this->data['provider'] === 'stripe')
            {
                $provider = 'stripe';
                $customer_id = $this->data['customer_id'];
            }
            $userTip = UserTip::where('tipper_email', $this->data['email'])->where('tippee_user_id', $userToTip->id)->where('is_active', 1)->first();
            if(! is_null($this->data['tip_frequency']) && $this->data['tip_frequency'] != 'one-off' && $this->data['tip_frequency'] != '' && is_null($userTip))
            {
                $userTip = UserTip::create([
                    'tipper_email' => $this->data['email'],
                    'tippee_user_id' => $userToTip->id,
                    'amount_in_flk' => $this->data['flk'],
                    'tip_frequency' => $this->data['tip_frequency'],
                    'originating_currency' => $originating_currency,
                    'originating_client_source' => $originating_client_source,
                    'originating_content_id' => $originating_content_id,
                    'last_tip' => now(),
                    'provider' => $provider,
                    'card_token' => $card_token,
                    'customer_id' => $customer_id,
                ]);

                ExternalCommunity::create([
                    'user_id' => $userToTip->id,
                    'email' => $this->data['email'],
                ]);
            }
            if (! is_null($this->data['last_tip']) && $this->data['last_tip'] != '')
            {
                $userTip = UserTip::where('id', $this->data['id'])->first();
                $userTip->last_tip = now();
                $userTip->save();
            }
            NotifyTippingJob::dispatch([
                'tipper' => '',
                'tipper_email' => $this->data['email'],
                'tippee' => $userToTip,
                'amount_in_flk' => $creator_share_in_flk,
                'wallet_transaction' => $transaction,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
}
