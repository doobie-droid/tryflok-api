<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\AssetResource;

class ContentResource extends JsonResource
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
            'cover' => !is_null($this->cover) ? $this->cover->first() : null,
            'ratings_count' => $this->ratings_count,
            'ratings_average' => $this->ratings_avg_rating,
            'prices' => $this->prices,
            'tags' => $this->tags,
            'owner' => new UserResource($this->owner),
            'assets' => AssetResource::collection($this->assets),
        ]);
    }
}
