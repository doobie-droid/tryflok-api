<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class YoutubeVideoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $parent = parent::toArray($request);
        return array_merge($parent, [
            'title' => $this['title'],
            'embed_html' => $this['embed_html'],
            'embed_url' => $this['embed_url'],
            'thumbnail_url' => $this['thumbnail_url'],
            'description' => $this['description'],
        ]);
    }
}
