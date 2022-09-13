<?php 

use App\Models;

test('list exchange rates works', function()
{
    $configurations = Models\Configuration::factory()
    ->count(5)
    ->create();
    $response = $this->json('GET', "/api/v1/payments/exchange-rates");
    $response->assertStatus(200);
});