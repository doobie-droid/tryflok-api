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

class MigrateYoutubeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $url;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->url = $data['url'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = $this->url;

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
        $keywords = '';
        if ($str) {
        foreach ($matches[1] as $match) {
            $count = count($matches[1]);
            $keywords .= "$match";
            $i++;
            if ($count > $i) $keywords .= ", ";
        }
        } else {
        foreach ($matches[1] as $match) {
            $keyword[] = $match;
        }
        $keywords = $keyword;
        }
        return $keywords;
        }
}
