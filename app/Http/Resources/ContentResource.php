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
            'cover' => $this->getCover(),
            'ratings_count' => $this->ratings_count,
            'ratings_average' => $this->ratings_avg_rating,
            'prices' => $this->whenLoaded('prices'),
            'tags' => $this->whenLoaded('tags'),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'assets' => AssetResource::collection($this->whenLoaded('assets')),
            'metas' => $this->refactorMetas(),
        ]);
    }

    private function getCover()
    {
        $cover = $this->whenLoaded('cover');
        if (!is_null($cover)) {
            return $this->cover->first();
        }

        return null;
    }

    private function refactorMetas()
    {
        $metas = $this->whenLoaded('metas');
        $metasReworked = [];
        foreach ($metas as $meta) {
            $metasReworked[$meta->key] = $meta->value;
        }

        return $metasReworked;
    }
}
