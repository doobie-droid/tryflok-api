<?php

namespace Database\Factories;

use App\Constants\Constants;
use App\Models;
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
            'user_id' => Models\User::factory(),
            'revenueable_type' => 'content',
            'revenueable_id' => Models\Content::factory(),
            'referral_bonus' => 0,
            'added_to_payout' => 0,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul($amount, Constants::NORMAL_CREATOR_CHARGE, 6),
            'benefactor_share' => bcmul($amount, 1 - Constants::NORMAL_CREATOR_CHARGE, 6),
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

    public function tip()
    {
        return $this->state(function (array $attributes) {
            return [
                'revenue_from' => 'tip',
            ];
        });
    }

    public function customAmount($amount)
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'amount' => $amount,
                'platform_share' => bcmul($amount, Constants::NORMAL_CREATOR_CHARGE, 6),
                'benefactor_share' => bcmul($amount, 1 - Constants::NORMAL_CREATOR_CHARGE, 6),
            ];
        });
    }

    public function setCreatedAt($date)
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'created_at' => $date,
            ];
        });
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
