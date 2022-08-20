<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models;
use App\Models\ContentLike;

class ContentLikeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = ContentLike::class;

    public function definition()
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'content_id' => Models\Content::factory(),
            'user_id' => Models\User::factory(),
        ];
    }
}
