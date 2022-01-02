<?php

namespace Tests\Feature\Controllers\API\V1\UserController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class GetDashboardDetailsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_dashboard_returns_401_when_user_is_not_signed_in()
    {
        $user = Models\User::factory()->create();
        $response = $this->json('GET', '/api/v1/account/dashboard');
        $response->assertStatus(401);
    }

    public function test_get_dashboard_details_works()
    {
        $user = Models\User::factory()->create();
        $this->be($user);

        $previous_sales_revenues_amount = 100;
        $previous_sales_revenues_count = 10;
        Models\Revenue::factory()
            ->for($user, 'user')
            ->customAmount($previous_sales_revenues_amount)
            ->setCreatedAt(now()->subMonths(2))
            ->count($previous_sales_revenues_count)
            ->create();

        $current_month_sales_revenues_amount = 50;
        $current_month_sales_revenues_count = 5;
        Models\Revenue::factory()
            ->for($user, 'user')
            ->customAmount($current_month_sales_revenues_amount)
            ->count($current_month_sales_revenues_count)
            ->create();

        $expected_total_previous_sales_revenue = bcmul(
            $previous_sales_revenues_amount * $previous_sales_revenues_count, 
            100 - Constants\Constants::NORMAL_CREATOR_CHARGE, 
            2
        );

        $expected_total_current_month_sales_revenue = bcmul(
            $current_month_sales_revenues_amount * $current_month_sales_revenues_count, 
            100 - Constants\Constants::NORMAL_CREATOR_CHARGE, 
            2
        );

        $expected_total_sales_revenue = bcadd(
            $expected_total_previous_sales_revenue, 
            $expected_total_current_month_sales_revenue, 
            2
        );

        $previous_tips_revenues_amount = 80;
        $previous_tips_revenues_count = 20;
        Models\Revenue::factory()
            ->for($user, 'user')
            ->tip()
            ->customAmount($previous_tips_revenues_amount)
            ->setCreatedAt(now()->subMonths(2))
            ->count($previous_tips_revenues_count)
            ->create();

        $current_month_tips_revenues_amount = 60;
        $current_month_tips_revenues_count = 10;
        Models\Revenue::factory()
            ->for($user, 'user')
            ->tip()
            ->customAmount($current_month_tips_revenues_amount)
            ->count($current_month_tips_revenues_count)
            ->create();

        $expected_total_previous_tips_revenue = bcmul(
            $previous_tips_revenues_amount * $previous_tips_revenues_count, 
            100 - Constants\Constants::NORMAL_CREATOR_CHARGE, 
            2
        );

        $expected_total_current_month_tips_revenue = bcmul(
            $current_month_tips_revenues_amount * $current_month_tips_revenues_count, 
            100 - Constants\Constants::NORMAL_CREATOR_CHARGE, 
            2
        );

        $expected_total_tips_revenue = bcadd(
            $expected_total_previous_tips_revenue, 
            $expected_total_current_month_tips_revenue, 
            2
        );

        $digiverse1 = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->create();
        Models\Subscription::factory()
            ->for($digiverse1, 'subscriptionable')
            ->setCreatedAt(now()->subMonths(2))
            ->count(10)
            ->create();

        $digiverse2 = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->create();

        $first_date = now()->startOfMonth();
        Models\Subscription::factory()
            ->for($digiverse1, 'subscriptionable')
            ->setCreatedAt($first_date)
            ->count(2)
            ->create();
        $second_date = now()->startOfMonth()->addDays(3);
        Models\Subscription::factory()
            ->for($digiverse1, 'subscriptionable')
            ->count(3)
            ->setCreatedAt($second_date)
            ->create();

        $response = $this->json('GET', '/api/v1/account/dashboard');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'status_code',
            'message',
            'data' => [
                'total_tips',
                'month_tips',
                'total_sales',
                'month_sales',
                'total_subscribers',
                'month_subscribers',
                'subscription_graph',
            ]
        ]);
        $data = $response->getData()->data;
        $this->assertEquals($data->total_tips, $expected_total_tips_revenue);
        $this->assertEquals($data->month_tips, $expected_total_current_month_tips_revenue);
        $this->assertEquals($data->total_sales, $expected_total_sales_revenue);
        $this->assertEquals($data->month_sales, $expected_total_current_month_sales_revenue);
        $this->assertEquals($data->total_subscribers, 15);
        $this->assertEquals($data->month_subscribers, 5);
        $first_date_key = (string) $first_date->format('Y-m-d');
        $second_date_key = (string) $second_date->format('Y-m-d');
        $this->assertEquals($data->subscription_graph->$first_date_key, 2);
        $this->assertEquals($data->subscription_graph->$second_date_key, 3);
    }
}
