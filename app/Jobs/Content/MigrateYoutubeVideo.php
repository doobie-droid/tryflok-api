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
        
        $response = Http::asJson()
        ->get(
            'https://youtube.googleapis.com/youtube/v3/videos',
            [
                'part' => 'snippet,player,contentDetails',
                'id' => $videoId,
                'key' => config('services.google.youtube_api_key'),
            ]
            );

            $youtubeVideoData = [
                'title' => $response->json('items.0.snippet.title'),
                'embed_html' => $response->json('items.0.player.embedHtml'),
                'embed_url' => 'https://youtube.com/embed/'.$videoId,
                'thumbnail_url' => $this->thumbnailUrl($response),
                'description' => preg_replace('/#.*/', '', $response->json('items.0.snippet.description')),
            ];
            $descriptionHashTags = $this->get_hashtags($response->json('items.0.snippet.description'));

            $is_available = 0;
            $is_challenge = 0;

            if (is_null($youtubeVideoData))
            {
                return $this->respondBadRequest('This video is no longer available');
            }

            $content = Content::create([
                'title' => $youtubeVideoData['title'],
                'description' => $youtubeVideoData['description'],
                'user_id' => $user->id,
                'type' => 'video',
                'is_available' => $is_available,
                'approved_by_admin' => 1,
                'show_only_in_digiverses' => 1,
                'live_status' => 'inactive',
                'is_challenge' => $is_challenge,
            ]);

                $filename = date('Ymd') . Str::random(16);
                $folder = join_path('assets', Str::random(16) . date('Ymd'), 'text');
                $fullFilename = join_path($folder, $filename . '.html');
                $video_asset = Asset::create([
                    'url' => $youtubeVideoData['embed_url'],
                    'storage_provider' => 'youtube',
                    'storage_provider_id' => $fullFilename,
                    'asset_type' => 'video',
                    'mime_type' => 'video/html',
                ]);

                $cover_asset = Asset::create([
                    'url' => $youtubeVideoData['thumbnail_url'],
                    'storage_provider' => 'youtube',
                    'storage_provider_id' => $fullFilename,
                    'asset_type' => 'image',
                    'mime_type' => 'image/html',
                ]);
                $content->assets()->attach($cover_asset->id, [
                    'id' => Str::uuid(),
                    'purpose' => 'content-asset',
                ]);

                $content->assets()->attach($video_asset->id, [
                    'id' => Str::uuid(),
                    'purpose' => 'content-asset',
                ]);

            if (! is_null($descriptionHashTags))
            {
                foreach ($descriptionHashTags as $tags)
                {
                $tag = Tag::where('name', 'LIKE', "%{$tags}%")->first();
                if (is_null($tags))
                {   
                    $content->tags()->create([
                        'id' => Str::uuid(),
                        'name' => $tags,
                    ]);
                }

                $content->tags()->attach($tags, [
                    'id' => Str::uuid(),
                ]);                
                }
            }             

            $digiverse->contents()->attach($content->id, [
                'id' => Str::uuid(),
            ]);

            $content = Content::where('id', $content->id)
            ->eagerLoadBaseRelations()
            ->eagerLoadSingleContentRelations()
            ->first();
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }

    private function thumbnailUrl($response)
    {
        if($response->json('items.0.snippet.thumbnails.standard.url'))
        {
            return $response->json('items.0.snippet.thumbnails.standard.url');
        }

        return optional(collect($response->json('items.0.snippet.thumbnails'))
        ->sortByDesc('width')
        ->first()
        )['url'];
    }

    private function get_hashtags($description, $str = 1)
    {
        preg_match_all('/#(\w+)/',$description,$matches);
        $i = 0;
        $keywords = [];
        if ($str) {
        foreach ($matches[1] as $match) {
            $count = count($matches[1]);
            $keywords[] = strtolower($match);
            $i++;
        }
        }
        return array_unique($keywords);
    }
}
