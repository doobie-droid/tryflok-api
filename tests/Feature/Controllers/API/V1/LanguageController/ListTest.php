<?php

namespace Tests\Feature\Controllers\API\V1\LanguageController;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ListTest extends TestCase
{
    use DatabaseTransactions;

    public function test_list_languages_works()
    {
        $response = $this->json('GET', '/api/v1/languages');
        $response->assertStatus(200)->assertJsonStructure([
            'status_code',
            'message',
            'data' => [
                'languages' => [
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
