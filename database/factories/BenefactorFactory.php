<?php

namespace Database\Factories;

use App\Models\Benefactor;
use Illuminate\Database\Eloquent\Factories\Factory;

class BenefactorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Benefactor::class;

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
            'share' => 100,
        ];
    }
}
