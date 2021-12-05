<?php

namespace App\Jobs\Userables;

use App\Jobs\Userables\UpdateContentUserable as UpdateContentUserableJob;
use App\Models\Userable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateCollectionUserable implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $collection;
    public $user;
    public $parentUserable;
    public $status;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->collection = $data['collection'];
        $this->user = $data['user'];
        $this->status = $data['status'];
        $this->parentUserable = $data['parent_userable'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $userable = Userable::where('userable_type', 'collection')->where('userable_id', $this->collection->id)->where('user_id', $this->user->id)->where('parent_id', $this->parentUserable->id)->first();
        //in case another item was added newly to this collection
        if (is_null($userable)) {
            $userable = Userable::create([
                'user_id' => $this->user->id,
                'parent_id' => $this->parentUserable->id,
                'status' => $this->status,
                'userable_type' => 'collection',
                'userable_id' => $this->collection->id,
            ]);
        } else {
            $userable->status = $this->status;
            $userable->save();
        }

        //handle the item's contents in userables
        foreach ($this->collection->contents as $content) {
            UpdateContentUserableJob::dispatch([
                'content' => $content,
                'user' => $this->user,
                'parent_userable' => $userable,
                'status' => $this->status,
            ]);
        }
        //handle item's collections in userables
        foreach ($this->collection->childCollections as $collection) {
            UpdateCollectionUserable::dispatch([
                'collection' => $collection,
                'parent_userable' => $userable,
                'user' => $this->user,
                'status' => $this->status,
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
