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

class DispatchContentUserablesUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $deleted_contents, $collection, $new_contents;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->deleted_contents = $data['deleted_contents'];
        $this->new_contents = $data['new_contents'];
        $this->collection = $data['collection'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $deleted_contents = $this->deleted_contents;
        $new_contents = $this->new_contents;
        $collection = $this->collection;
        Userable::where('userable_type', 'collection')->where('userable_id', $this->collection->id)
        ->chunk(10000, function ($userables) use ($collection, $deleted_contents, $new_contents){
            foreach ($userables as $userable) {
                UpdateContentUserablesJob::dispatch([
                    'deleted_contents' => $deleted_contents,
                    'new_contents' => $new_contents,
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
