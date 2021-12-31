<?php

namespace Tests\Feature\Controllers\API\V1\CountryController;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ListTest extends TestCase
{
    use DatabaseTransactions;

    public function test_list_countries_works()
    {
        $response = $this->json('GET', '/api/v1/countries');
        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'countries' => [
                    [
                        'id',
                        'name',
                        'iso_code',
                    ],
                ],
            ],
        ]);
    }
}
