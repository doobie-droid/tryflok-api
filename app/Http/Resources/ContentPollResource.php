<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentPollResource extends JsonResource
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
            'poll_options' => new ContentPollOptionsResource($this->whenLoaded('pollOptions')),   
            'content' =>new ContentResource($this->whenLoaded('content')),
        ]);
    }
}
