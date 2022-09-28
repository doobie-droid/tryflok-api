<?php
namespace App\Services\Youtube;

use Illuminate\Support\Facades\Cache;

class Test
{
    protected $base_url;
    protected $secret;

    public function __construct()
    {
        $this->base_url = "https://youtube.googleapis.com/youtube/v3/videos?";
        $this->secret = config('services.google.youtube_api_key');
    }

    public function fetchVideo(string $videoId): \stdClass
    {
    $key = "{$this->base_url}id={$videoId}&key={$this->secret}&part=snippet,contentDetails";

    $response = json_decode((string) Cache::get($key), true);

    return response()->json($response)->getData();
    }
    
}