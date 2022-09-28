<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ContentPoll;
use App\Models\Content;
use App\Models\User;


class ContentPollFactory extends Factory
{
     /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ContentPoll::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
            return [
                'id' => $this->faker->unique()->uuid,
                'question' => $this->faker->sentence(4),
                'closes_at' => now()->addHours(5),
                'content_id' => Content::factory(),
                'user_id' => User::factory(),
            ];
    }
}
