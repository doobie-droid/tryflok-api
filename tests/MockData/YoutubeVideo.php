<?php 

namespace Tests\MockData;

class YoutubeVideo
{
    public static function generateStandardGetYoutubeVideoResponse(): array
    {
        return [
            'status_code',
            'message',
            'data' => [
                'youtubeVideoData' => self::STANDARD_GET_STRUCTURE,
                'descriptionHashTags',
            ]
        ];
    }

    public static function generateStandardUrlErrorResponse(): array
    {
        return [
            'message' => 'Invalid or missing input fields',
            'errors' => [
                'url' => 
                [
                    'The provided Url is not a valid youtube url.'
                ]
            ]  
        ];
    }

    const STANDARD_GET_STRUCTURE = [
        'title',
        'embed_html',
        'embed_url',
        'thumbnail_url',
        'description',
    ];
}