<?php

namespace App\Jobs\Collection;

use App\Models\Userable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateContentUserables implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $deleted_contents;
    public $new_contents;
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
        $this->deleted_contents = $data['deleted_contents'];
        $this->new_contents = $data['new_contents'];
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
        //get ids of synced content
        $deleted_ids = [];
        foreach ($this->deleted_contents as $content) {
            $deleted_ids[] = $content->id;
        }
        //get the deleted userables and delete them
        $deleted_userables = Userable::where('userable_type', 'content')->whereIn('userable_id', $deleted_ids)->where('user_id', $this->user_id)->where('parent_id', $this->userable_id)->get();
        foreach ($deleted_userables as $userable) {
            $userable->delete();
        }
        //add the new userables
        foreach ($this->new_contents as $content) {
            Userable::create([
                'user_id' => $this->user_id,
                'parent_id' => $this->userable_id,
                'status' => $this->userable_status,
                'userable_type' => 'content',
                'userable_id' => $content->id,
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
