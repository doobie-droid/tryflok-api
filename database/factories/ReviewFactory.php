<?php

namespace Database\Factories;

use App\Models\Content;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'rating' => 5,
            'comment' => 'A comment',
            'reviewable_type' => 'content',
            'reviewable_id' => Content::factory(),
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
