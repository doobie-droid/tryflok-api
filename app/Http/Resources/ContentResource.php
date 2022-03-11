<?php

namespace App\Http\Resources;

use App\Http\Resources\AssetResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\ContentChallengeContestantResource;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'challenge_contestants' => ContentChallengeContestantResource::collection($this->whenLoaded('challenge_contestants')),
            'assets' => AssetResource::collection($this->whenLoaded('assets')),
            'metas' => $this->refactorMetas(),
            'total_challenge_contributions' => $this->challenge_contributions_sum_amount,
            'voting_result' => $this->getVoteStructure(),
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

    private function refactorMetas()
    {
        $metas = $this->whenLoaded('metas');
        $metasReworked = null;
        foreach ($metas as $meta) {
            $metasReworked[$meta->key] = $meta->value;
        }

        return $metasReworked;
    }

    private function getVoteStructure()
    {
        $challenge_contestants = $this->whenLoaded('challenge_contestants');
        if (! is_null($challenge_contestants)) {
            $total_votes = $this->challengeVotes()->count();
            $vote_data = [
                'total_votes' => $total_votes,
                'contestants' => [],
            ];
            foreach ($challenge_contestants as $contestant_entry) {
                $votes = $this->challengeVotes()->where('contestant_id', $contestant_entry->contestant_id)->count();
                $data = [
                    'contestant' => new UserResource($this->whenLoaded($contestant_entry->contestant)),
                    'votes' => $votes,
                ];
                $vote_data['contestants'][] = $data;
            }

            return $vote_data;
        }
        return null;
    }
}
