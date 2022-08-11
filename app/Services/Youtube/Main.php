<?php
namespace App\Services\Youtube;

use App\Services\API;
use GuzzleHttp\ClientException;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

class Main extends API
{
    protected $secret;
    protected $base_url;

    public function __construct()
    {
        $this->secret = config('services.google.youtube_api_key');
        $this->base_url = "https://youtube.googleapis.com/youtube/v3/videos?";
    }

    public function baseUrl(): string
    {
        return 'https://youtube.googleapis.com/youtube/v3/videos?';
    }

    public function fetchVideo(string $videoId)
    {
        try {
            $results = $this->getClient()->GET("{$this->base_url}id={$videoId}&key={$this->secret}&part=snippet,contentDetails");
            $res  = json_decode((string) $results->getBody(), true);
            return response()->json($res)->getData();
        } catch (ClientException $exception) {
            return response()->json([
               'status' => false,
               'status_code' => $exception->getCode(),
               'message' => $exception->getMessage(),
            ])->getData();
        }
    }

    protected function setupStackHeaders($stack)
    {
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $request = $request->withHeader('Content-Type', 'application/json');
            return $request;
        }));

        return $stack;
    }
}