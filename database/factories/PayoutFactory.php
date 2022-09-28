<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayoutFactory extends Factory
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
            'amount' => 3,
            'claimed' => 0,
            'handler' => null,
            'reference' => null,
            'last_payment_request' => null,
            'cashout_attempts' => 0,
            'cancelled_by_admin' => 0,
            'failed_notification_sent' => null,
        ];
    }
}
