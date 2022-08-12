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




class MigrateYoutubeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
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
        $url = $this->url;
        $digiverse = $this->digiverse;
        $user = $this->user;
        $price_in_dollars = $this->price_in_dollars;

        parse_str( parse_url( $url, PHP_URL_QUERY ), $my_array_of_vars );

        $videoId = $my_array_of_vars['v']; 


        $youtube = new Youtube;
        $response = $youtube->fetchVideo($videoId);

            $youtubeVideoData = [
                'title' => $response->items[0]->snippet->title,
                'embed_url' => 'https://youtube.com/embed/'.$videoId,
                'thumbnail_url' => $this->thumbnailUrl($response),
                'description' => preg_replace('/#.*/', '', $response->items[0]->snippet->description),
                'tags' => array_unique($response->items[0]->snippet->tags),
            ];


            if (is_null($youtubeVideoData))
            {
                return $this->respondBadRequest('This video is no longer available');
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
                ]);

                $cover_asset = Asset::create([
                    'url' => $youtubeVideoData['thumbnail_url'],
                    'storage_provider' => 'youtube',
                    'storage_provider_id' => $videoId,
                    'asset_type' => 'image',
                    'mime_type' => 'image/jpeg',
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
                
            if (! is_null($youtubeVideoData['tags']))
            {   
                foreach ($youtubeVideoData['tags'] as $tag)
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
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }

    private function thumbnailUrl($response)
    {
        if($response->items[0]->snippet->thumbnails->default->url)
        {
            return $response->items[0]->snippet->thumbnails->default->url;
        }

        return optional(collect($response->items[0]->snippet->thumbnails)
        ->sortByDesc('width')
        ->first()
        )['url'];
    }
}
