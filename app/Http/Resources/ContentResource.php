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
            'ratings' => $this->ratings_avg_rating,
            'ratings_count' => $this->ratings->where('rating', '>', 0)->count(),
            'ratings_average' => $this->ratings->where('rating', '>', 0)->avg('rating'),
            'prices' => $this->prices,
            'tags' => $this->tags,
            'owner' => new UserResource($this->owner),
            'assets' => AssetResource::collection($this->assets),
        ]);
    }
}
