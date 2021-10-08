<?php

namespace App\Jobs\Collection;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApproveCollectionChildren implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $collection;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->collection = $data['collection'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //get all contents and approve
        foreach ($this->collection->contents as $content) {
            $content->approved_by_admin = 1;
            $content->save();
        }
        //get all collections, approve and then dispatch for it's contents to be approved too
        foreach ($this->collection->childCollections as $childCollection) {
            $childCollection->approved_by_admin = 1;
            $childCollection->save();
            self::dispatch([
                'collection' => $childCollection,
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
