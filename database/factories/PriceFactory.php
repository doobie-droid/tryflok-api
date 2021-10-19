<?php

namespace Database\Factories;

use App\Models\Price;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Price::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'amount' => $this->faker->randomFloat(2, 5, 100),
            'interval' => 'one-off',
            'interval_amount' => 1,
        ];
    }

    public function free()
    {
        return $this->state(function (array $attributes) {
            return [
                'amount' => 0,
                'interval' => 'one-off',
            ];
        });
    }

    public function subscription()
    {
        return $this->state(function (array $attributes) {
            return [
                'amount' => $this->faker->randomFloat(2, 5, 100),
                'interval' => 'monthly',
            ];
        });
    }
}
