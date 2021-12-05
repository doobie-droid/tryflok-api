<?php

namespace App\Http\Resources;

use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

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
        return array_merge($parent, [
            'ratings_count' => $this->ratings_count,
            'ratings_average' => $this->ratings_avg_rating,
            'cover' => $this->getCover(),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'prices' => $this->whenLoaded('prices'),
            'tags' => $this->whenLoaded('tags'),
        ]);
    }

    private function getCover()
    {
        $cover = $this->whenLoaded('cover');
        if (! is_null($cover)) {
            return $this->cover->first();
        }

        return null;
    }
}
