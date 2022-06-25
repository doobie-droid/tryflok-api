<?

test('respond unauthorized works', function(){
    $response = $this->json('GET', '/api/v1/auth/unauthenticated');
    $response->assertStatus(401)->assertJsonStructure([
    'status',
    'message',
    'status_code',
    'errors',
    ]);
});