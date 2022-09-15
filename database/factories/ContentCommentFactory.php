<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Content;

class ContentCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'comment' => $this->faker->unique()->sentence(4),
            'content_id' => Content::factory(),
            'user_id' => User::factory(),
        ];
    }
}
