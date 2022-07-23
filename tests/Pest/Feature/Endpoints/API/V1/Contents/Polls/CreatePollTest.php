<?php 

use App\Models;

it('returns a 401 error when a user is not signed in', function()
{
            $user = Models\User::factory()->create();
            $content = Models\Content::factory()
            ->for($user, 'owner')
            ->create();

            $request = [
                'question' => 'question',
                'closes_at' => now()->addHours(5),
                'content_id' => $content->id,
                'user_id' => $content->user_id,
                'option' => [
                    0 => 'option 1',
                    1 =>'option 2',
                ],
            ];
            $response = $this->json('POST', "/api/v1/contents/{$content->id}/create", $request);     
            $response->assertStatus(401);
});

test('poll is not created if signed in user is not the owner of the content', function()
{
            $user = Models\User::factory()->create();
            $this->be($user);
            $content = Models\Content::factory()
            ->create();

            $request = [
                'question' => 'question',
                'closes_at' => now()->addHours(5),
                'content_id' => $content->id,
                'user_id' => $content->user_id,
                'option' => [
                    0 => 'option 1',
                    1 =>'option 2',
                ],
            ];
            $response = $this->json('POST', "/api/v1/contents/{$content->id}/create", $request);     
            $response->assertStatus(400);
});

//This checks should include asserting that the poll question and it's options can be found in the appropriate tables
test('poll is created if signed in user is owner of the content', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->for($user, 'owner')
        ->create();

        $request = [
            'question' => 'question',
            'closes_at' => now()->addHours(5),
            'content_id' => $content->id,
            'user_id' => $content->user_id,
            'option' => [
                0 => 'option 1',
                1 =>'option 2',
            ],
        ];
        $response = $this->json('POST', "/api/v1/contents/{$content->id}/create", $request); 
        $response->assertStatus(200);
})->only();