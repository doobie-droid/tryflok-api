<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ViewFactory extends Factory
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
            'user_id' => User::factory(),
        ];
    }

    public function createdDaysAgo($days_count = 1)
    {
        return $this->state(function (array $attributes) use ($days_count) {
            return [
                'created_at' => now()->subDays($days_count),
            ];
        });
    }
}
