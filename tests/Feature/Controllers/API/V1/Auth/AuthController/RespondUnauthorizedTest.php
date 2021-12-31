<?php

namespace Tests\Feature\Controllers\API\V1\Auth\AuthController;

use Tests\TestCase;

class RespondUnauthorizedTest extends TestCase
{
   public function test_respond_unauthorized_works()
   {
    $response = $this->json('GET', '/api/v1/auth/unauthenticated');
    $response->assertStatus(401)->assertJsonStructure([
        'status',
        'message',
        'status_code',
        'errors',
    ]);
   }
}
