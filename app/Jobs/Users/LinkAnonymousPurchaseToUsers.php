<?php

namespace App\Jobs\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models;

class LinkAnonymousPurchaseToUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $anonymous_purchase;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($anonymous_purchase)
    {
        $this->anonymous_purchase = $anonymous_purchase;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info("Begin Linking Anonymous Purchases To Users");
            $anonymous_email = $this->anonymous_purchase->email;
            $user = Models\User::where('email', $anonymous_email)->first();
            if (! is_null($user)) {
                Models\Userable::create([
                    'user_id' => $user->id,
                    'status' => 'available',
                    'userable_type' => $this->anonymous_purchase->anonymous_purchaseable_type,
                    'userable_id' => $this->anonymous_purchase->anonymous_purchaseable_id,
                ]);

                $this->anonymous_purchase->link_user_id = $user->id;
                $this->anonymous_purchase->save();
            }
            Log::info("End Linking Anonymous Purchases To Users");
        } catch (\Exception $exception) {
            throw $exception;
            Log::error($exception);
        }
    }
}
