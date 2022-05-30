<?php

namespace Tests\Feature\Controllers\API\V1\AnalyticsController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GetDailySalesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_daily_sales_returns_401_when_user_is_not_signed_in()
    {
        $user = Models\User::factory()->create();
        $response = $this->json('GET', '/api/v1/analytics/sales/daily');
        $response->assertStatus(401);
    }

    public function test_get_daily_sales_works()
    {
        $user = Models\User::factory()->create();
        $this->be($user);

        $day1 = now()->startOfMonth()->addDays(2)->format('Y-m-d');
        $day2 = now()->startOfMonth()->addDays(4)->format('Y-m-d');
        $day3 = now()->startOfMonth()->addDays(8)->format('Y-m-d');

        $day1_sales_amount = 100;
        $day1_sales_count = 10;
        Models\Revenue::factory()
            ->for($user, 'user')
            ->customAmount($day1_sales_amount)
            ->setCreatedAt($day1)
            ->count($day1_sales_count)
            ->create();
        $day1_expected_total = $day1_sales_amount * $day1_sales_count;
        
        $day2_sales_amount = 400;
        $day2_sales_count = 5;
        Models\Revenue::factory()
            ->for($user, 'user')
            ->customAmount($day2_sales_amount)
            ->setCreatedAt($day2)
            ->count($day2_sales_count)
            ->create();
        $day2_expected_total = $day2_sales_amount * $day2_sales_count;

        $day3_sales_amount = 400;
        $day3_sales_count = 5;
        Models\Revenue::factory()
            ->for($user, 'user')
            ->customAmount($day3_sales_amount)
            ->setCreatedAt($day3)
            ->count($day3_sales_count)
            ->create();
        $day3_expected_total = $day3_sales_amount * $day3_sales_count;

        $response = $this->json('GET', '/api/v1/analytics/sales/daily');

        $response->assertStatus(200)
        ->assertJsonStructure([
            'status_code',
            'message',
            'data' => [
                'sales' => [
                    $day1,
                    $day2,
                    $day3,
                ]
            ]
        ]);
        $data = $response->getData()->data;
        $this->assertEquals($data->sales->$day1, bcmul(
            $day1_expected_total,
            1 - Constants\Constants::NORMAL_CREATOR_CHARGE,
            2
        ));
        $this->assertEquals($data->sales->$day2, bcmul(
            $day2_expected_total,
            1 - Constants\Constants::NORMAL_CREATOR_CHARGE,
            2
        ));
        $this->assertEquals($data->sales->$day3, bcmul(
            $day3_expected_total,
            1 - Constants\Constants::NORMAL_CREATOR_CHARGE,
            2
        ));
    }
}
