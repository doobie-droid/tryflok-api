<?php

namespace Tests\Feature\Controllers\API\V1\CategoryController;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ListTest extends TestCase
{
    use DatabaseTransactions;

    public function test_list_categories_works()
    {
        $response = $this->json('GET', '/api/v1/categories');
        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'categories' => [
                    [
                        'id',
                        'name',
                    ],
                ],
            ],
        ]);
    }
}
