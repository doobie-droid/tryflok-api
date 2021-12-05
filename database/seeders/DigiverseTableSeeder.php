<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Benefactor;
use App\Models\Collection;
use App\Models\Price;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Tests\MockData\User as UserMock;

class DigiverseTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::where('email', UserMock::SEEDED_USER['email'])->first();
        $tags = Tag::all();
        $tag_ids = [];
        foreach ($tags as $tag) {
            $tag_ids[] = $tag->id;
        }
        Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->count(20)
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user->id
            ])
        )
        ->create()
        ->each(function ($digiverse) use ($tag_ids) {
            $cover = Asset::factory()->create();
            $tag = $tag_ids[rand(0, count($tag_ids) - 1)];

            $digiverse->cover()->attach($cover->id, [
                'id' => Str::uuid(),
                'purpose' => 'cover'
            ]);

            $digiverse->tags()->attach($tag, [
                'id' => Str::uuid(),
            ]);
        });
    }
}
