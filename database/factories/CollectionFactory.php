<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CollectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Collection::class;

    /**
     * @var Collection 
     */ 
    private $digiverse;

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
     * @var Content[]
     */ 
    private $contents;

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
            'type' => 'digiverse',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_collections' => 0,
            'views' => 0,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Collection $digiverse) {
            $this->digiverse = $digiverse;
            $this->generateCover();
            $this->generateBenefactor();
            $this->generatePrice();
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

    public function digiverse()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'digiverse',
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

    public function setCover(Asset $cover): self
    {
        $this->cover = $cover;
        return $this->afterCreating(function (Collection $digiverse) {
            $this->digiverse = $digiverse;
            $this->generateCover();
        });
    }

    public function setPriceAmount(float $priceAmount): self
    {
        $this->priceAmount = $priceAmount;
        return $this->afterCreating(function (Collection $digiverse) {
            $this->digiverse = $digiverse;
            $this->generatePrice();
        });
    }

    /**
     * @param Tag[] $tags
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this->afterCreating(function (Collection $digiverse) {
            $this->digiverse = $digiverse;
            $this->generateTags();
        });
    }

    /**
     * @param Content[] $contents
     */
    public function setContents(array $contents): self
    {
        $this->contents = $contents;
        return $this->afterCreating(function (Collection $digiverse) {
            $this->digiverse = $digiverse;
            $this->generateContents();
        });
    }

    private function generateCover(): void
    {
        $previousCover = $this->digiverse->cover()->first();
        if (! is_null($previousCover)) {
            $this->digiverse->cover()->detach($previousCover->id);
            $previousCover->forceDelete();
        }
        $this->cover = $this->cover ?? Asset::factory()->create();
        $this->digiverse->cover()->attach($this->cover->id, [
            'id' => Str::uuid(),
            'purpose' => 'cover',
        ]);
    }

    private function generateBenefactor(): void
    {
        $this->digiverse->benefactors()->create([
            'user_id' => $this->digiverse->user_id,
            'share' => 100,
        ]);
    }

    private function generatePrice(): void
    {   
        $previousPrice = $this->digiverse->prices()->first();
        if (! is_null($previousPrice)) {
            $previousPrice->forceDelete();
        }
        $this->digiverse->prices()->create([
            'amount' => $this->priceAmount,
            'interval' => 'monthly',
            'interval_amount' => 1,
        ]);
    }

    private function generateTags(): void
    {
        foreach ($this->tags as $tag) {
            $this->digiverse->tags()->attach($tag->id, [
                'id' => Str::uuid(),
            ]);
        }
    }

    private function generateContents(): void
    {
        foreach ($this->contents as $content) {
            $this->digiverse->contents()->attach($content->id, [
                'id' => Str::uuid(),
            ]);
        }
    }
}
