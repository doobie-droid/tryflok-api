<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ExternalCommunity;
use App\Models\User;
use Illuminate\Support\Str;

class ExternalCommunityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */

      /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ExternalCommunity::class;

    public function definition()
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'user_id' => User::factory(),
        ];
    }
}
