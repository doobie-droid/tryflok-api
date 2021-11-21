<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Content;
use App\Models\Userable;
use App\Models\Price;

class SubscriptionFactory extends Factory
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
            'userable_id' => Userable::factory(),
            'price_id' => Price::factory(),
            'subscriptionable_type' => 'content',
            'subscriptionable_id' => Content::factory(),
            'status' => 'active',
            'auto_renew' => 1,
            'start' => now(),
            'end' => now()->add(1, 'month'),
        ];
    }
}
