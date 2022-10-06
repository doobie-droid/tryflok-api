<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class PaymentAccountFactory extends Factory
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
            'provider'=> 'flutterwave',
            'identifier' => '02172451124',
            'country_code' => 'NG',
            'currency_code' => 'NGN',
            'bank_name' => 'GTBank Plc',
            'bank_code' => '058',
            'branch_code' => '0998',
            'branch_name' => 'Adertiu'
        ];
    }
}
