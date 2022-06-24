<?php

namespace Tests\Feature\Controllers\API\V1\TagController;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ListTest extends TestCase
{
    use DatabaseTransactions;

    public function test_list_tags_works()
    {
        $response = $this->json('GET', '/api/v1/tags');
        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'tags' => [
                    [
                        'id',
                        'name',
                    ],
                ],
            ],
        ]);
    }
}
