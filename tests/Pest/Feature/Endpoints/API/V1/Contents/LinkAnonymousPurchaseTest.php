<?php 

use App\Models;
use Tests\MockData;
use Illuminate\Support\Str;

beforeEach(function()
{
    $this->user = Models\User::factory()->create();
    $this->content = Models\Content::factory()->create();
    $this->email = "charlesagate@gmail.com";
    $this->access_token = Str::random(10) . date('Ymd');

    $this->anonymousPurchase = Models\AnonymousPurchase::create([
        'email' => $this->email,
        'name' => 'John Doe',
        'access_token' => $this->access_token,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $this->content->id,
        'status' => 'available'
    ]);
    $this->request = [
        'access_tokens' => [
            [
                'access_token' => $this->access_token,
            ]
        ],
    ];
});
test('user who is not signed in cannot link purchase', function()
{
    $response = $this->json('POST', "/api/v1/contents/anonymous-purchase-link", $this->request);
    $response->assertStatus(401);
});

test('link anonymous purchase works with valid access code', function()
{
    $this->be($this->user);
    $response = $this->json('POST', "/api/v1/contents/anonymous-purchase-link", $this->request);
    $response->assertStatus(200);
    $this->assertDatabaseHas('userables', [
        'user_id' => $this->user->id,
        'status' => 'available',
        'userable_type' => 'content',
        'userable_id' => $this->content->id,
    ]);

    $this->assertDatabaseHas('anonymous_purchases', [
        'email' => $this->email,
        'name' => 'John Doe',
        'access_token' => $this->access_token,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $this->content->id,
        'status' => 'available',
        'link_user_id' => $this->user->id,
    ]);
});

it('does not work with invalid access code', function()
{
    $this->be($this->user);
    $request = [
        'access_tokens' => [
            'access_token' => '1234rhfgt',

        ],
    ];
    $response = $this->json('POST', "/api/v1/contents/anonymous-purchase-link", $request);
    $response->assertStatus(400);
    $this->assertDatabaseMissing('userables', [
        'user_id' => $this->user->id,
        'status' => 'available',
        'userable_type' => 'content',
        'userable_id' => $this->content->id,
    ]);
});