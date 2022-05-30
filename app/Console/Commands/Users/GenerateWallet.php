<?php

namespace App\Console\Commands\Users;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:generate-user-wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Wallet for users that might have been missed during sign up';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            User::doesntHave('wallet')->chunk(100000, function ($users) {
                foreach ($users as $user) {
                    $user->wallet()->create([]);
                }
            });
        } catch (\Exception $exception) {
            throw $exception;
        }
        return 0;
    }
}
