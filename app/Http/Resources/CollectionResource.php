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
        return array_merge($parent , [
            'ratings_average' => $this->ratings_avg_rating,
            'cover' => !is_null($this->cover) ? $this->cover->first() : null,
            'owner' => new UserResource($this->owner),
            'prices' => $this->prices,
            'tags' => $this->tags,
        ]);
    }
}
