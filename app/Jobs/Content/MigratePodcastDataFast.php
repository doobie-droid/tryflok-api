<?php

namespace App\Jobs\Content;

use Illuminate\Bus\Queueable;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\Podcast\PodcastApi;
use App\Models\Content;
use App\Models\Asset;
use App\Models\Collection;
use Illuminate\Support\Str;
use App\Jobs\Content\MigratePodcastAudio as MigratePodcastAudioJob;
use App\Models\Podcast as PodcastModel;
use App\Traits\SendMail;

class MigratePodcastDataFast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendMail;

    public $rssLink;
    public $digiverse;
    public $user;
    public $podcastError;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //
        $this->rssLink = $data['rssLink'];
        $this->digiverse = $data['digiverse'];
        $this->user = $data['user'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $rssLink = $this->rssLink;
        $digiverse = $this->digiverse;
        $user = $this->user;
        $podcast = new PodcastApi;
        $response = $podcast->fetchPodcastData($rssLink,'xml');
        if (!$response) {
            $this->podcastError = "You have entered an invalid Rss Link";
            $this->sendMail();
            return;
        }
        $response = json_decode(json_encode($response), true);
        $podcast_collection = Collection::create([
            'title' => $response["channel"]["image"]["title"],
            'description' => $response["channel"]["description"] != [] ? $response["channel"]["description"] : null,
            'user_id' => $user->id,
            'type' => 'channel',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_collections' => 1,
        ]);
        $podcast_collection_cover_asset = Asset::create([
            'url' => $response["channel"]["image"]["url"],
            'storage_provider' => 'public-s3',
            'storage_provider_id' => 'public-s3',
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
            'processing_complete' => 1,
        ]);

        $podcast_collection->cover()->attach($podcast_collection_cover_asset->id, [
            'id' => Str::uuid(),
            'purpose' => 'cover',
        ]);

        $podcast_rssLink = PodcastModel::create([
            'digiverse_id' => $digiverse->id,
            'podcast_id' => $podcast_collection->id,
            'rss_link' => $this->rssLink,
        ]);
        $arraylength = count($response["channel"]["item"]);
        foreach ($response["channel"]["item"] as $key=>$value) {
            
            MigratePodcastAudioJob::dispatch([
                'user' => $this->user,
                'podcast_collection' => $podcast_collection,
                'description' =>  $value["description"] != [] ? $value["description"] : null ,
                'title' => $value["title"] != [] ? $value["title"] : 'Chapter '.$arraylength - $key,
                'published_date' => $value["pubDate"],
                //TODO1 the audio link files have lengths that are longer than the space allocated for it since the data type "string" has a maximum char length of 255 characters 
                'audio_link' => $value["enclosure"]["@attributes"]["url"],
                'cover_picture_id' => $podcast_collection_cover_asset->id,
            ]);
        }



        $digiverse->childCollections()->attach($podcast_collection->id, [
            'id' => Str::uuid(),
        ]);
    }

    public function failed(\Throwable $exception)

    {
        
        $this->sendMail();
        Log::error($exception);
    }
}
