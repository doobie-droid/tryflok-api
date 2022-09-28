<?php

namespace App\Jobs\Collection;

use App\Jobs\Collection\UpdateContentUserables as UpdateContentUserablesJob;
use App\Models\Userable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchSubCollectionUserablesUpdate implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $deleted_collections;
    public $collection;
    public $new_collections;
    public $userable_id;
    public $user_id;
    public $userable_status;
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
        $this->userable_id = $data['userable_id'];
        $this->user_id = $data['user_id'];
        $this->userable_status = $data['userable_status'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // get the deleted collection userables under this guy
        $deleted_ids = [];
        $mapped_deleted = [];
        foreach ($this->deleted_collections as $collection) {
            $deleted_ids[] = $collection->id;
            $mapped_deleted[$collection->id] = $collection;
        }
        $deletedSubUserables = Userable::where('userable_type', 'collection')->whereIn('userable_id', $deleted_ids)->where('user_id', $this->user_id)->where('parent_id', $this->userable_id)->get();
        foreach ($deletedSubUserables as $userable) {
            //remove it's contents
            UpdateContentUserablesJob::dispatch([
                'deleted_contents' => $mapped_deleted[$userable->userable_id]->contents()->get(),
                'new_contents' => [],
                'userable_id' => $userable->id,
                'user_id' => $userable->user->id,
                'userable_status' => $userable->status,
            ]);
            //remove the child collection from this collection
            //add it's sub-colelctions
            self::dispatch([
                'collection' => $mapped_deleted[$userable->userable_id],
                'deleted_collections' => $mapped_deleted[$userable->userable_id]->childCollections()->get(),
                'new_collections' => [],
                'userable_id' => $userable->id,
                'user_id' => $userable->user->id,
                'userable_status' => $userable->status,
            ]);
            //delete this
            $userable->delete();
        }

        //add the new userables
        foreach ($this->new_collections as $collection) {
            $userable = Userable::create([
                'user_id' => $this->user_id,
                'parent_id' => $this->userable_id,
                'status' => $this->userable_status,
                'userable_type' => 'collection',
                'userable_id' => $collection->id,
            ]);
            //add it's contents
            UpdateContentUserablesJob::dispatch([
                'deleted_contents' => [],
                'new_contents' => $collection->contents()->get(),
                'userable_id' => $userable->id,
                'user_id' => $userable->user->id,
                'userable_status' => $userable->status,
            ]);
            //add it's sub-colelctions
            self::dispatch([
                'collection' => $collection,
                'deleted_collections' => [],
                'new_collections' => $collection->childCollections()->get(),
                'userable_id' => $userable->id,
                'user_id' => $userable->user->id,
                'userable_status' => $userable->status,
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
