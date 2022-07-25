<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ContentPollVote;
use App\Models\Content;
use App\Models\ContentPollOption;
use App\Models\User;


class ContentPollVoteFactory extends Factory
{   
     /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ContentPollVote::class;

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
                'content_poll_option_id' => ContentPollOption::factory(),
                'voter_id' => User::class,
                'ip' => $this->faker->unique()->localIpv4(),
        ];
    }
}
