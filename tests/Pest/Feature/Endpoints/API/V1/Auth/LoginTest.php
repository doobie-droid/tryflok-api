<?
use App\Constants;
use App\Models;
use Tests\MockData;

beforeEach(function () {
    $this->user = Models\User::factory()->create();
    $this->user->assignRole(Constants\Roles::SUPER_ADMIN);
    $this->user->assignRole(Constants\Roles::ADMIN);
});

test('login works via email', function () {
    $request = [
        'username' => $this->user->email,
        'password' => 'password',
    ];

    $response = $this->json('POST', '/api/v1/auth/login', $request);
    $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
        ->assertJson(MockData\User::generateStandardUserResponseJson($this->user->name, $this->user->email, $this->user->username, [Constants\Roles::SUPER_ADMIN, Constants\Roles::ADMIN, Constants\Roles::USER]));
});

test('login works via username', function(){
   
    $request = [
        'username' => $this->user->username,
        'password' => 'password',
    ];
    $response = $this->json('POST', '/api/v1/auth/login', $request);
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
    ->assertJson(MockData\User::generateStandardUserResponseJson($this->user->name, $this->user->email, $this->user->username, [Constants\Roles::SUPER_ADMIN, Constants\Roles::ADMIN, Constants\Roles::USER]));
});

test('refresh token works', function(){
        $this->be($this->user);
        $token = JWTAuth::fromUser($this->user);
        $server = [
            'HTTP_Authorization' => 'Bearer ' . $token,
        ]; 
        $response = $this->json('PATCH', '/api/v1/account/token', [], $server);
        $response->assertStatus(200)->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE);
});