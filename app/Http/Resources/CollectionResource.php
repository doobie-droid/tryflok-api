<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ContentResource;
use App\Http\Resources\UserResource;

class CollectionResource extends JsonResource
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
        unset($parent["child_collections"]);
        unset($parent["ratings_avg_rating"]);
        return array_merge($parent , [
            'cover' => !is_null($this->cover) ? $this->cover->first() : null,
            'ratings' => $this->ratings_avg_rating,
            'owner' => new UserResource($this->owner),
            'prices' => $this->prices,
            'tags' => $this->tags,
            'collections' => self::collection($this->whenLoaded('childCollections')),
            'contents' => ContentResource::collection($this->whenLoaded('contents')),
        ]);
    }
}
