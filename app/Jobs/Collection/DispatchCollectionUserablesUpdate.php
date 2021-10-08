<?php

namespace App\Jobs\Collection;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Userable;
use App\Jobs\Collection\DispatchSubCollectionUserablesUpdate as DispatchSubCollectionUserablesUpdateJob;

class DispatchCollectionUserablesUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $collection, $deleted_collections, $new_collections;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->deleted_collections = $data['deleted_collections'];
        $this->new_collections = $data['new_collections'];
        $this->collection = $data['collection'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $deleted_collections = $this->deleted_collections;
        $new_collections = $this->new_collections;
        $collection = $this->collection;

        Userable::where('userable_type', 'collection')->where('userable_id', $this->collection->id)
        ->chunk(10000, function ($userables) use ($collection, $deleted_collections, $new_collections){
            foreach ($userables as $userable) {
                DispatchSubCollectionUserablesUpdateJob::dispatch([
                    'collection' => $collection,
                    'deleted_collections' => $deleted_collections,
                    'new_collections' => $new_collections,
                    'userable_id' => $userable->id,
                    'user_id' => $userable->user->id,
                    'userable_status' => $userable->status,
                ]);
            }
        });
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
        //TO DO: mail the user telling them the edit failed?
    }
}
