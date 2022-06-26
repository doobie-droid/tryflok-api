<?
use App\Models;

test('update password works', function(){
    $user = Models\User::factory()->create();
        $this->be($user);
        $response = $this->json('PUT', '/api/v1/account/password', [
            'old' => 'password',
            'password' => 'user126',
            'password_confirmation' => 'user126',
        ]);
        $response->assertStatus(200);
        $this->assertTrue(Hash::check('user126', $user->password));
});