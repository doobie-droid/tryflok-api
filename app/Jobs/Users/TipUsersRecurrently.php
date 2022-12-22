<?php

namespace App\Jobs\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models;
use App\Models\UserTip;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Constants\Constants;
use App\Mail\User\FailedTipMail;

class TipUsersRecurrently implements ShouldQueue
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
        try {
            $datas = $this->data;
            foreach ($datas as $userTip)
            {
            $amount_in_flk = $userTip->amount_in_flk;
            $tipper = Models\User::where('id', $userTip->tipper_user_id)->first();
            $tippee = Models\User::where('id', $userTip->tippee_user_id)->first();
            $next_tip = false;
            switch ($userTip->tip_frequency) {
                case 'daily':
                    if($userTip->last_tip <= now()->subDay())
                    {
                        $next_tip = true;
                    }
                    break;
                case 'weekly':
                    if($userTip->last_tip <= now()->subWeek())
                    {
                        $next_tip = true;
                    }
                    break;                
                case 'monthly':
                    if($userTip->last_tip <= now()->subMonth())
                    {
                        $next_tip = true;
                    }
                    break;
                default:
                    Log::info('Invalid tip frequency');
                    continue 2;
            }

            if(! $next_tip)
            {
                Log::info("Not yet time for next tip");
                continue;
            }
            if ((float) $tipper->wallet->balance < (float) $amount_in_flk) {
                Log::info("Not enough flk balance");
                $this->sendFailedTipMail($tipper, $tippee);
                continue;
            }

            $amount_in_dollars = bcdiv($amount_in_flk, 100, 6);
            $newWalletBalance = bcsub($tipper->wallet->balance, $amount_in_flk, 2);
            $transaction = Models\WalletTransaction::create([
                'wallet_id' => $tipper->wallet->id,
                'amount' => $amount_in_flk,
                'balance' => $newWalletBalance,
                'transaction_type' => 'deduct',
                'details' => "You gifted @{$tippee->username} {$amount_in_flk} Flok Cowries",
            ]);
            $transaction->payments()->create([
                'payer_id' => $tipper->id,
                'payee_id' => $tippee->id,
                'amount' => $amount_in_dollars,
                'payment_processor_fee' => 0,
                'provider' => 'wallet',
                'provider_id' => $transaction->id,
            ]);
            $tipper->wallet->balance = $newWalletBalance;
            $tipper->wallet->save();

            $platform_charge = Constants::TIPPING_CHARGE;
            if ($tippee->user_charge_type === 'non-profit') {
                $platform_charge = Constants::TIPPING_CHARGE;
            }
            $platform_share = bcmul($amount_in_dollars, $platform_charge, 6);
            $creator_share = bcmul($amount_in_dollars, 1 - $platform_charge, 6);

            $revenue = $tippee->revenues()->create([
                'revenueable_type' => 'user',
                'revenueable_id' => $tippee->id,
                'amount' => $amount_in_dollars,
                'payment_processor_fee' => 0,
                'platform_share' => $platform_share,
                'benefactor_share' => $creator_share,
                'referral_bonus' => 0,
                'revenue_from' => 'tip',
                'added_to_payout' => 1,
            ]);

            if ( ! is_null($userTip->originating_currency)) {
                $revenue->originating_currency = $userTip->originating_currency;
            }

            if ( ! is_null($userTip->originating_content_id)) {
                $revenue->originating_content_id = $userTip->originating_content_id;
            }

            if ( ! is_null($userTip->originating_client_source)) {
                $revenue->originating_client_source = $userTip->originating_client_source;
            }

            $revenue->save();

            if (! is_null($revenue->originating_content_id)) {
                $content_tip_count = $tippee->revenues()->where('originating_content_id', $revenue->originating_content_id)->where('revenue_from', 'tip')->count();
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
            $newWalletBalance = bcadd($tippee->wallet->balance, $creator_share_in_flk, 2);
            $transaction = WalletTransaction::create([
                'wallet_id' => $tippee->wallet->id,
                'amount' => $creator_share_in_flk,
                'balance' => $newWalletBalance,
                'transaction_type' => 'fund',
                'details' => "@{$tipper->username} gifted you {$creator_share_in_flk} Flok Cowries",
            ]);
            $tippee->wallet->balance = $newWalletBalance;
            $tippee->wallet->save();

            $userTip->last_tip = now();
            $userTip->save();
            DB::commit();
            
            NotifyTippingJob::dispatch([
                'tipper' => $tipper,
                'tippee' => $tippee,
                'amount_in_flk' => $creator_share_in_flk,
                'wallet_transaction' => $transaction,
            ]);     
        }
        }catch (\Exception $exception) {
            Log::error($exception);
        }
    }

    public function sendFailedTipMail($tipper, $tippee)
    {
        $message = "Sorry, we could not tip {$tippee->username} because you do not have enough flk in your wallet";
        Mail::to($tipper)->send(new FailedTipMail([
        'user' => $tipper,
        'message' => $message,
    ]));
    }
}