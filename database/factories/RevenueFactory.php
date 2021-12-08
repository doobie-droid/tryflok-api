<?php

namespace Database\Factories;

use App\Constants\Constants;
use App\Models\Content;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RevenueFactory extends Factory
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
            'revenueable_type' => 'content',
            'revenueable_id' => Content::factory(),
            'referral_bonus' => 0,
            'added_to_payout' => 0,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul($amount, Constants::NORMAL_CREATOR_CHARGE, 6),
            'benefactor_share' => bcmul($amount, 100 - Constants::NORMAL_CREATOR_CHARGE, 6),
            'revenue_from' => 'sale',
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
                'platform_share' => bcmul($amount, Constants::NORMAL_CREATOR_CHARGE, 6),
                'benefactor_share' => bcmul($amount, 100 - Constants::NORMAL_CREATOR_CHARGE, 6),
            ];
        });
    }
}
