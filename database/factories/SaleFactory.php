<?php

namespace Database\Factories;

use App\Constants\Constants;
use App\Models\Content;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $amount = 75.32;
        return [
            'id' => $this->faker->unique()->uuid,
            'amount' => $amount,
            'user_id' => User::factory(),
            'saleable_type' => 'content',
            'saleable_id' => Content::factory(),
            'referral_bonus' => 0,
            'added_to_payout' => 0,
            'payment_processor_fee' => 0,
            'platform_share' => bcdiv(bcmul($amount, Constants::PLATFORM_SHARE, 6), 100, 2),
            'benefactor_share' => bcdiv(bcmul($amount, Constants::CREATOR_SHARE, 6), 100, 2),
        ];
    }

    public function addedToPayout()
    {
        return $this->state(function (array $attributes) {
            return [
                'added_to_payout' => 1,
            ];
        });
    }

    public function customAmount($amount)
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'amount' => $amount,
                'platform_share' => bcdiv(bcmul($amount, Constants::PLATFORM_SHARE, 6), 100, 2),
                'benefactor_share' => bcdiv(bcmul($amount, Constants::CREATOR_SHARE, 6), 100, 2),
            ];
        });
    }
}
