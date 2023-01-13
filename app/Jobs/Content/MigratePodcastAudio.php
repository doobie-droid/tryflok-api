<?php

namespace App\Jobs\Content;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\SendMail;
use Illuminate\Support\Facades\Log;
use App\Models\Content;
use App\Models\Asset;
use Illuminate\Support\Str;
class MigratePodcastAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendMail;


    public $user;
    public $podcastError;
    public $published_date;
    public $title;
    public $audio_link;
    public $podcast_collection;
    public $description;
    public $cover_picture_id;

    public function __construct($data)
    {
        $this->published_date = $data['published_date'];
        $this->podcast_collection = $data['podcast_collection'];
        $this->user = $data['user'];
        $this->audio_link = $data['audio_link'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->cover_picture_id = $data['cover_picture_id'];
    }

    public function handle()
    {

        $content = Content::create([
            'title' => $this->title,
            'description' => $this->description,
            'user_id' => $this->user->id,
            'type' => 'audio',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
            'live_status' => 'inactive',
            'is_challenge' => 0,
        ]);

        $audio_asset = Asset::create([
            'url' => $this->audio_link,
            'storage_provider' => 'public-s3',
            'storage_provider_id' => 'public-s3',
            'asset_type' => 'audio',
            'mime_type' => 'audio/mpeg',
            'processing_complete' => 1,
        ]);

        $content->assets()->attach($audio_asset->id, [
            'id' => Str::uuid(),
            'purpose' => 'content-asset',
        ]);

        $content->assets()->attach($this->cover_picture_id, [
            'id' => Str::uuid(),
            'purpose' => 'cover',
        ]);

        $content->prices()->create([
            'interval' => 'one-off',
            'interval_amount' => 1,
        ]);  

        $this->podcast_collection->contents()->attach($content->id, [
            'id' => Str::uuid(),
        ]);
        $content->benefactors()->create([
            'user_id' => $this->user->id,
            'share' => 100,
        ]);
    }

    public function failed(\Throwable $exception)
    {
        $this->sendMail();
        Log::error($exception);
    }
}
