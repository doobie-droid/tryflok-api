<?php

namespace App\Jobs\Userables;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Userable;

class UpdateContentUserable implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $content, $user, $parentUserable, $status;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->content = $data['content'];
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
        $childUserable = Userable::where('userable_type', 'content')->where('userable_id', $this->content->id)->where('user_id', $this->user->id)->where('parent_id', $this->parentUserable->id)->first();
        //in case another item was added newly to this collection
        if (is_null($childUserable)) {
            Userable::create([
                'user_id' => $this->user->id,
                'parent_id' => $this->parentUserable->id,
                'status' => $this->status,
                'userable_type' => 'content',
                'userable_id' => $this->content->id,
            ]);
        } else {
            $childUserable->status = $this->status;
            $childUserable->save();
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
