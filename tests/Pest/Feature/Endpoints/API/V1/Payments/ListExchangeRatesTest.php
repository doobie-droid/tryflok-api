<?php 

use App\Models;

test('list exchange rates works', function()
{
        $configuration1 = Models\Configuration::factory()
        ->create([
            'type' => 'exchange_rate',
            'name' => 'NGN_TO_USD',
            'value' => 400
        ]);

        $configuration2 = Models\Configuration::factory()
        ->create([
            'type' => 'exchange_rate',
            'name' => 'USD_TO_NGN',
            'value' => 20
        ]);

        $configuration3 = Models\Configuration::factory()
        ->create([
            'type' => 'exchange_rate',
            'name' => 'NGN_TO_EUR',
            'value' => 500
        ]);
        $response = $this->json('GET', "/api/v1/payments/exchange-rates");
        $response->assertStatus(200);
});

test('only configurations of type exchange rate are returned', function()
{
        $configurations = Models\Configuration::factory()
        ->count(5)
        ->create([
            'type' => 'exchange_rate',
        ]);

        $configurations = Models\Configuration::factory()
        ->count(3)
        ->create();
        $response = $this->json('GET', "/api/v1/payments/exchange-rates");
        $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'exchangeRates' => [
                        '*' => [
                                'id',
                                'name',
                                'type',
                                'value',
                        ]
                ],
            ],
        ]);
        $this->assertEquals(count($response->getData()->data->exchangeRates), 5);
});