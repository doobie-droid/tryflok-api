<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResourceWithSensitive extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return array_merge(parent::toArray($request), [
            'roles' => $this->whenLoaded('roles'),
            'profile_picture' => !is_null($this->profile_picture) ? $this->profile_picture->first() : null,
            'wallet' => $this->whenLoaded('wallet'),
        ]);
    }
}
