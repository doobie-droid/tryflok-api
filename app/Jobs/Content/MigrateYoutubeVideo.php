<?php

namespace App\Jobs\Content;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Rules\YoutubeUrl;
use Illuminate\Support\Facades\Log;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Tag;
use Illuminate\Support\Str;
use App\Models\Asset;
use App\Http\Resources\ContentResource;
use App\Services\Youtube\Youtube;
use App\Utils\RestResponse;
use App\Mail\User\YoutubeMigrateMail;
use Illuminate\Support\Facades\Mail;




class MigrateYoutubeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use RestResponse;
    
    public $url;
    public $digiverse;
    public $user;
    public $price_in_dollars;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->url = $data['url'];
        $this->digiverse = $data['digiverse'];
        $this->user = $data['user'];
        $this->price_in_dollars = $data['price_in_dollars'];

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {   
        Log::info("Begin Youtube Migration");
        $url = $this->url;
        $digiverse = $this->digiverse;
        $user = $this->user;
        $price_in_dollars = $this->price_in_dollars;
        Log::info("Video url: ".$url);
        preg_match("/(\/|%3D|v=|vi=)([0-9A-z-_]{11})([%#?&]|$)/", $url, $match);
        if (! empty($match))
        {
            $videoId = $match[2];
        }
        if (empty($match))
        {
            parse_str( parse_url( $url, PHP_URL_QUERY ), $array );
 
             $index = array_key_first($array);
             
             $value = $array[$index];
 
             if (($value) != '')
             {
                 $videoId = $value;
             }
             else{
                 $videoId = $index;
             }
        }
        $youtube = new Youtube;
        $response = $youtube->fetchVideo($videoId);

        if (count($response->items) == 0)
        {
            Log::info("Video is no longer available");
            Log::info(json_encode($response));
            $this->sendMail();
            return;
        }

        $youtubeVideoData = [
            'title' => $response->items[0]->snippet->title,
            'embed_url' => 'https://youtube.com/embed/'.$videoId,
            'thumbnail_url' => $this->thumbnailUrl($response),
            'description' => preg_replace('/#.*/', '', $response->items[0]->snippet->description),
        ];
        $tags = [];
        if (isset($response->items[0]->snippet->tags)){
                $tags = $response->items[0]->snippet->tags;
            }  

        $content = Content::create([
            'title' => $youtubeVideoData['title'],
            'description' => $youtubeVideoData['description'],
            'user_id' => $user->id,
            'type' => 'video',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
            'live_status' => 'inactive',
            'is_challenge' => 0,
        ]);

        $video_asset = Asset::create([
            'url' => $youtubeVideoData['embed_url'],
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'video',
            'mime_type' => 'video/mp4',
            'processing_complete' => 1,
        ]);

        $cover_asset = Asset::create([
            'url' => $youtubeVideoData['thumbnail_url'],
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
            'processing_complete' => 1,
        ]);
        $content->assets()->attach($cover_asset->id, [
            'id' => Str::uuid(),
            'purpose' => 'cover',
        ]);

        $content->assets()->attach($video_asset->id, [
            'id' => Str::uuid(),
            'purpose' => 'content-asset',
        ]);  

        $content->prices()->create([
            'amount' => $price_in_dollars,
            'interval' => 'one-off',
            'interval_amount' => 1,
        ]);  

        $content->benefactors()->create([
            'user_id' => $user->id,
            'share' => 100,
        ]);
            
        if (! empty($tags))
            {   
                foreach ($tags as $tag)
                {
                    $check_tag = Tag::where('name', $tag)->first();
                    if (is_null($check_tag))
                    {   
                       $check_tag = Tag::create([
                            'id' => Str::uuid(),
                            'name' => $tag,
                        ]);
                    }
                    $content->tags()->attach($check_tag->id, [
                        'id' => Str::uuid(),
                    ]);                
            }             
            }

        $digiverse->contents()->attach($content->id, [
            'id' => Str::uuid(),
        ]);
        Log::info("End Youtube Migration");
    }

    public function failed(\Throwable $exception)
    {
        $this->sendMail();
        Log::error($exception);
    }

    private function thumbnailUrl($response)
    {
        if (! empty( $response->items[0]->snippet->thumbnails->maxres)) {
            $thumbnail_url = $response->items[0]->snippet->thumbnails->maxres->url;
        }
        else{
            $videoID = $response->items[0]->id;
            $thumbnail_url = 'https://i.ytimg.com/vi/'.$videoID.'/0.jpg';
        }
        return $thumbnail_url;
    }

    public function sendMail()
    {
            $message = "Youtube video migration failed for {$this->url} because the video does not exist";
            Mail::to($this->user)->send(new YoutubeMigrateMail([
            'user' => $this->user,
            'message' => $message,
        ]));
    }
}
