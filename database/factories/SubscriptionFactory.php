<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Collection;
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
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => Collection::factory(),
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
