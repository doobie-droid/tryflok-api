<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tag;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TagPriority extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:tag-priority';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change tag priority in database';

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
        $tags = file(public_path("Tags.csv"));
        foreach ($tags as $tag) {
            $tag = strToLower($tag);
            $tags = explode("&",$tag);
            foreach ($tags as $tag) {
                $dbTag = Tag::where('name', $tag)->first();
                if ( ! is_null($dbTag)) {
                    $dbTag->tag_priority = 1;
                    $dbTag->save();
                }
                if ( is_null($dbTag)) {
                    Tag::create([
                        'name' => $tag,
                        'tag_priority' => 1,
                    ]);
                }    
            }        
        };
        return Command::SUCCESS;
    }
}
