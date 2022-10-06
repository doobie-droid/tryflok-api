<?php

use App\Models;
use Tests\MockData;
use Illuminate\Support\Str;

test('only users with at least one published content can withdraw', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);

        // $digiverse = Models\Collection::factory()->digiverse()->create([
        //     'user_id' => $user->id,
        // ]);
        // Models\Content::factory()
        // ->setDigiverse($digiverse)
        // ->setTags([Models\Tag::factory()->create()])
        // ->create([
        //     'approved_by_admin' => 1,
        //     'is_available' => 1,
        //     'user_id' => $user->id,
        // ]);
        // Models\Content::factory()
        // ->setDigiverse($digiverse)
        // ->setTags([Models\Tag::factory()->create()])
        // ->count(4)
        // ->create([
        //     'user_id' => $user->id,
        // ]);

        $digiverse = Models\Collection::factory()->digiverse()->create([
            'user_id' => $user->id,
        ]);
        $collection = Models\Collection::factory()->collection()->create([
            'user_id' => $user->id,
        ]);

        $digiverse->childCollections()->attach($collection->id, [
            'id' => Str::uuid(),
        ]);

        $collection->contents()->create([
            ''
        ]);
        
        $response = $this->json('PATCH', "/api/v1/account/withdraw-from-wallet", $request);
        dd($response);
        $response->assertStatus(200);
});