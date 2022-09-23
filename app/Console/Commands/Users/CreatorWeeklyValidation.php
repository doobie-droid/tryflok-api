<?php

namespace App\Console\Commands\Users;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models;
use App\Jobs\Users\CompileWeeklyAnalytics as CompileWeeklyAnalyticsJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class CreatorWeeklyValidationEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:send-weekly-validation-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send validation emails to creators weekly';

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
        try{
            User::
            whereHas('digiversesCreated', function (Builder $query) {
                $query->where('is_available', 1)
                ->where('approved_by_admin', 1)
                ->whereNull('archived_at')
                ->whereHas('contents', function (Builder $query) {
                    $query->where('is_available', 1)
                    ->where('approved_by_admin', 1)
                    ->whereNull('archived_at')
                    ->where(function ($query) {
                        $query->whereNull('live_ended_at')
                        ->orWhereDate('live_ended_at', '>=', now()->subHours(12));
                    });
                });
            })
            ->chunk(1000, function ($users) {                
                foreach ($users as $user) {
                CompileWeeklyAnalyticsJob::dispatch($user);
                }
            });
        } catch (\Exception $exception) {
            throw $exception;
        }
        return Command::SUCCESS;
    }
}