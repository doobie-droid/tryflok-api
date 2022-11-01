<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Meta;
use App\Models\Collection;
use App\Models\Content;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Content::class;

    /**
 * @var Collection
*/
    private $digiverse;
    private $collection;

    /**
 * @var Tag[]
*/
    private $tags = [];

    /**
 * @var float
*/
    private $priceAmount = 0;
    
    /**
 * @var Asset
*/
    private $cover;

    /**
 * @var Asset
*/
    private $asset;

    /**
 * @var Content
*/
    private $content;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'title' => $this->faker->unique()->sentence(4),
            'description' => $this->faker->sentence(40),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['audio', 'video', 'pdf', 'newsletter', 'live-audio', 'live-video']),
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
            'live_status' => 'inactive',
            'trending_points' => 0,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Content $content) {
            $this->content = $content;
            $this->generateMetas();
            $this->generateDigiverse();
            $this->generateCover();
            $this->generateAsset();
            $this->generateBenefactor();
            $this->generatePrice();
        });
    }

    public function futureScheduledDate()
    {
        return $this->state(function (array $attributes) {
            return [
                'scheduled_date' => now()->addDays(10),
            ];
        });
    }

    public function audio()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'audio',
            ];
        });
    }

    public function video()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'video',
            ];
        });
    }

    public function pdf()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'pdf',
            ];
        });
    }

    public function newsletter()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'newsletter',
            ];
        });
    }

    public function liveAudio()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'live-audio',
            ];
        });
    }

    public function liveVideo()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'live-video',
            ];
        });
    }

    public function unavailable()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_available' => 0,
            ];
        });
    }

    public function live()
    {
        return $this->state(function (array $attributes) {
            return [
                'live_status' => 'active',
            ];
        });
    }

    public function not_approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'approved_by_admin' => 0,
            ];
        });
    }

    public function liveEnded($ended_at = null)
    {
        if (is_null($ended_at)) {
            $ended_at = now();
        }

        return $this->state(function (array $attributes) use ($ended_at) {
            return [
                'live_status' => 'ended',
                'live_ended_at' => $ended_at,
            ];
        });
    }

    public function isChallenge()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_challenge' => 1,
            ];
        });
    }


    public function setDigiverse(Collection $digiverse): self
    {
        $this->digiverse = $digiverse;
        return $this->afterCreating(function (Content $content) {
            $this->content = $content;
            $this->generateDigiverse();
        });
    }

    public function setCollection(Collection $collection): self
    {
        $this->collection = $collection;
        return $this->afterCreating(function (Content $content) {
            $this->content = $content;
            $this->generateCollection();
        });
    }

    /**
 * @param User[] $contestants
*/
    public function setChallengeContestants(array $contestants, array $options = ['accept' => 0]): self
    {
        return $this->afterCreating(function (Content $content) use ($contestants, $options) {
            $accept_count = 0;
            foreach ($contestants as $contestant) {
                $status = 'pending';
                if ($options['accept'] - $accept_count > 0) {
                    $status = 'accepted';
                }
                $accept_count++;
                $content->challengeContestants()->create([
                    'user_id' => $contestant->id,
                    'status' => $status,
                ]);
            }
        });
    }

    /**
 * @param User[] $contributors
*/
    public function setChallengeContributors(array $contributors, int $amount = 500): self
    {
        return $this->afterCreating(function (Content $content) use ($contributors, $amount) {
            foreach ($contributors as $contributor) {
                $content->challengeContributions()->create([
                    'user_id' => $contributor->id,
                    'amount' => $amount,
                ]);
            }
        });
    }

    public function setChallengeVoters(array $voters, string $contestant_id): self
    {
        return $this->afterCreating(function (Content $content) use ($voters, $contestant_id) {
            foreach ($voters as $voter) {
                $content->challengeVotes()->create([
                    'voter_id' => $voter->id,
                    'contestant_id' => $contestant_id,
                ]);
            }
        });
    }

    public function setChallengeDetails(int $pot_size = 0, int $minimum_contribution = 0, int $moderator_share = 10, int $winner_share = 60, int $loser_share = 30): self
    {
        return $this->afterCreating(function (Content $content) use ($pot_size, $minimum_contribution, $moderator_share, $winner_share, $loser_share) {
            $content->metas()->createMany([
                [
                    'key' => 'pot_size',
                    'value' => $pot_size,
                ],
                [
                    'key' => 'minimum_contribution',
                    'value' => $minimum_contribution,
                ],
                [
                    'key' => 'moderator_share',
                    'value' => $moderator_share,
                ],
                [
                    'key' => 'winner_share',
                    'value' => $winner_share,
                ],
                [
                    'key' => 'loser_share',
                    'value' => $loser_share,
                ],
            ]);
        });
    }

    public function noDigiverse(): self
    {
        return $this->afterCreating(function (Content $content) {
            $previousDigiverse = $content->collections()->first();
            if (! is_null($previousDigiverse)) {
                $content->collections()->detach($previousDigiverse->id);
                $previousDigiverse->forceDelete();
            }
        });
    }

    public function setCover(Asset $cover): self
    {
        $this->cover = $cover;
        return $this->afterCreating(function (Content $content) {
            $this->content = $content;
            $this->generateCover();
        });
    }

    public function setAsset(Asset $asset): self
    {
        $this->asset = $asset;
        return $this->afterCreating(function (Content $content) {
            $this->content = $content;
            $this->generateAsset();
        });
    }

    public function setPriceAmount(float $priceAmount): self
    {
        $this->priceAmount = $priceAmount;
        return $this->afterCreating(function (Content $content) {
            $this->content = $content;
            $this->generatePrice();
        });
    }

    /**
 * @param Tag[] $tags
*/
    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this->afterCreating(function (Content $content) {
            $this->content = $content;
            $this->generateTags();
        });
    }

    private function generateMetas(): void
    {
        $this->content->metas()->createMany([
            [
                'key' => 'channel_name',
                'value' => "{$this->content->id}-" . date('Ymd'),
            ],
            [
                'key' => 'rtc_token',
                'value' => '',
            ],
            [
                'key' => 'rtm_token',
                'value' => '',
            ],
            [
                'key' => 'join_count',
                'value' => 0,
            ],
        ]);
    }

    private function generateDigiverse(): void
    {
        $previousDigiverse = $this->content->collections()->first();
        if (! is_null($previousDigiverse)) {
            $this->content->collections()->detach($previousDigiverse->id);
            $previousDigiverse->forceDelete();
        }
        $this->digiverse = $this->digiverse ?? Collection::factory()
                                                        ->digiverse()
                                                        ->state(['user_id' => $this->content->user_id])
                                                        ->create();
        $this->digiverse->contents()->attach($this->content->id, [
            'id' => Str::uuid(),
        ]);
    }

    private function generateCollection(): void
    {
        $previousCollection = $this->content->basicCollections()->first();
        if (! is_null($previousCollection)) {
            $this->content->basicCollections()->detach($previousCollection->id);
            $previousCollection->forceDelete();
        }
        $this->collection = $this->collection ?? Collection::factory()
                                                        ->collection()
                                                        ->state(['user_id' => $this->content->user_id])
                                                        ->create();
        $this->collection->contents()->attach($this->content->id, [
            'id' => Str::uuid(),
        ]);
    }

    private function generateCover(): void
    {
        $previousCover = $this->content->cover()->first();
        if (! is_null($previousCover)) {
            $this->content->cover()->detach($previousCover->id);
            $previousCover->forceDelete();
        }
        $this->cover = $this->cover ?? Asset::factory()->create();
        $this->content->cover()->attach($this->cover->id, [
            'id' => Str::uuid(),
            'purpose' => 'cover',
        ]);
    }

    private function generateAsset(): void
    {
        $previousAsset = $this->content->assets()->first();
        if (! is_null($previousAsset)) {
            $this->content->assets()->detach($previousAsset->id);
            $previousAsset->forceDelete();
        }

        $this->asset = $this->asset ?? Asset::factory()->state([
            'asset_type' => $this->content->type,
        ])->create();
        $this->content->assets()->attach($this->asset->id, [
            'id' => Str::uuid(),
            'purpose' => 'content-asset',
        ]);
    }

    private function generateBenefactor(): void
    {
        $this->content->benefactors()->create([
            'user_id' => $this->content->user_id,
            'share' => 100,
        ]);
    }

    private function generatePrice(): void
    {
        $previousPrice = $this->content->prices()->first();
        if (! is_null($previousPrice)) {
            $previousPrice->forceDelete();
        }
        $this->content->prices()->create([
            'amount' => $this->priceAmount,
            'interval' => 'one-off',
            'interval_amount' => 1,
        ]);
    }

    private function generateTags(): void
    {
        foreach ($this->tags as $tag) {
            $this->content->tags()->attach($tag->id, [
                'id' => Str::uuid(),
            ]);
        }
    }
}
