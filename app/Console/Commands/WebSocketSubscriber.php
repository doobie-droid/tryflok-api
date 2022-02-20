<?php

namespace App\Console\Commands;

use App\Constants\Constants;
use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class WebSocketSubscriber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:start-websocket-subscriber';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the redis listener for the websocket';

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
        $ws_identity = Cache::store('redis_local')->rememberForever('ws-identity', function () {
            return Str::random(8) . date('YmdHis');
        });
        
        Redis::subscribe(Constants::WEBSOCKET_MESSAGE_CHANNEL, function ($message) use ($ws_identity) {
            $websocket_client = new \WebSocket\Client(config('services.websocket.url'));
            /*$websocket_client->text(json_encode([
                'event' => 'app-update-rtm-channel-subscribers-count',
                'channel_name' => $channel->value,
                'subscribers_count' => $subscribers_count,
            ]));*/
            $websocket_client->close();
        });
        return Command::SUCCESS;
    }
}
