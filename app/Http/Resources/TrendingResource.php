<?php

namespace App\Http\Resources;

use App\Http\Resources\CollectionResource;
use App\Http\Resources\ContentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TrendingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $parent = parent::toArray($request);
        return array_merge($parent, [
            'reviewable' => $this->reviewable_type === 'content' ? new ContentResource($this->whenLoaded('reviewable')) : new CollectionResource($this->whenLoaded('reviewable')),
        ]);
    }
}
