<?
use App\Constants;
use App\Models;
use Tests\MockData;
use App\Notifications\User\EmailConfirmation;


beforeEach(function(){
    $this->request = MockData\User::REGISTRATION_REQUEST;
}); 

test('registration without referrer works', function(){
    Notification::fake();
    $request = MockData\User::REGISTRATION_REQUEST;
    $response = $this->json('POST', '/api/v1/auth/register', $request);
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
    ->assertJson(
        MockData\User::generateStandardUserResponseJson($request['name'], $request['email'], $request['username'], [Constants\Roles::USER])
    );
    $user = Models\User::where('email', $request['email'])->first();
    Notification::assertSentTo(
        [$user],
        EmailConfirmation::class
    );

    $this->assertDatabaseHas('users', [
        'email' => $request['email'],
        'name' => $request['name'],
        'phone_number' => $request['phone_number'],
    ]);
});

test('invalid usernames are not registered', function(){
    $this->request['username'] = 'e-rtra';
        $response = $this->json('POST', '/api/v1/auth/register', $this->request);
        $response->assertStatus(400);

        $this->request['username'] = 'user@';
        $response = $this->json('POST', '/api/v1/auth/register', $this->request);
        $response->assertStatus(400);

        $this->request['username'] = 'user#';
        $response = $this->json('POST', '/api/v1/auth/register', $this->request);
        $response->assertStatus(400);
});

test('valid usernames are registered', function(){
        $this->request['username'] = 'the_user9';
        $this->request['email'] = $this->faker->unique()->safeEmail;
        $response = $this->json('POST', '/api/v1/auth/register', $this->request);
        $response->assertStatus(200);

        $this->request['username'] = '9the_user9';
        $this->request['email'] = $this->faker->unique()->safeEmail;
        $response = $this->json('POST', '/api/v1/auth/register', $this->request);
        $response->assertStatus(200);

        $this->request['username'] = '_9the_user9';
        $this->request['email'] = $this->faker->unique()->safeEmail;
        $response = $this->json('POST', '/api/v1/auth/register', $this->request);
        $response->assertStatus(200);
});

test('verify email works', function(){
    $email_token = Str::random(16) . 'YmdHis';
        $user = Models\User::factory()
        ->state([
            'email_verified' => 0,
            'email_token' => $email_token,
        ])
        ->create();

        $request = [
            'token' => $email_token,
        ];
        $response = $this->json('PATCH', '/api/v1/auth/email', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
        ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
        ->assertJson(
            MockData\User::generateStandardUserResponseJson($user->name, $user->email, $user->username, [Constants\Roles::USER])
        );

        $this->assertDatabaseHas('users', [
            'email' => $user->email,
            'email_token' => '',
            'email_verified' => 1,
        ]);
});