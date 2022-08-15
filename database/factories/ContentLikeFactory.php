<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models;

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
            'likeable_type' => 'content',
            'likeable_id' => Models\Content::factory(),
            'user_id' => User::factory(),
        ];
    }
}
