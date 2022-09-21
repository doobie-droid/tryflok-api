<?php

namespace App\Console\Commands\Assets;

use App\Jobs\Assets\MigratePublic as MigratePublicAssetJob;
use App\Models\Asset;
use Illuminate\Console\Command;

class MigratePublicAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:migrate-public-assets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change the domain name for public assets';

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
        Asset::where('storage_provider', 'public-s3')->chunk(1000, function ($assets) {
            foreach ($assets as $asset) {
                MigratePublicAssetJob::dispatch($asset);
            }
        });
        return 0;
    }
}
