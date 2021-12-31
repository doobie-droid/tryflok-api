<?php

namespace Database\Factories;

use App\Models;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'userable_id' => Models\Userable::factory(),
            'price_id' => Models\Price::factory(),
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => Models\Collection::factory(),
            'status' => 'active',
            'auto_renew' => 1,
            'start' => now(),
            'end' => now()->add(1, 'month'),
        ];
    }

    public function doNotAutoRenew()
    {
        return $this->state(function (array $attributes) {
            return [
                'auto_renew' => 0,
            ];
        });
    }
}
