<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ContentPollOption;
use App\Models\ContentPoll;


class ContentPollOptionFactory extends Factory
{   
     /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ContentPollOption::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
                'id' => $this->faker->unique()->uuid,
                'content_poll_id' => ContentPoll::factory(),
                'option' => $this->faker->word,
        ];
    }
}
