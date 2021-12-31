<?php

namespace Tests\Feature\Controllers\API\V1\ContinentController;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ListTest extends TestCase
{
    use DatabaseTransactions;

    public function test_list_cotinents_works()
    {
        $response = $this->json('GET', '/api/v1/continents');
        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'continents' => [
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
