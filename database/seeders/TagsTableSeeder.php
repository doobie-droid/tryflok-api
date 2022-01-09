<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Tag::create(['id' => Str::uuid(), 'name' => 'horror']);
        Tag::create(['id' => Str::uuid(), 'name' => 'crypto']);
        Tag::create(['id' => Str::uuid(), 'name' => 'fashion']);
        Tag::create(['id' => Str::uuid(), 'name' => 'adventure']);
        Tag::create(['id' => Str::uuid(), 'name' => 'educative']);
        Tag::create(['id' => Str::uuid(), 'name' => 'african']);
        Tag::create(['id' => Str::uuid(), 'name' => 'gaming']);
        Tag::create(['id' => Str::uuid(), 'name' => 'sci-fi']);
        Tag::create(['id' => Str::uuid(), 'name' => 'fantasy']);
        Tag::create(['id' => Str::uuid(), 'name' => 'romance']);
        Tag::create(['id' => Str::uuid(), 'name' => 'comedy']);
        Tag::create(['id' => Str::uuid(), 'name' => 'filmmaking']);
        Tag::create(['id' => Str::uuid(), 'name' => 'photography']);
        Tag::create(['id' => Str::uuid(), 'name' => 'poetry']);
        Tag::create(['id' => Str::uuid(), 'name' => 'design']);
        Tag::create(['id' => Str::uuid(), 'name' => 'creative writing']);
        Tag::create(['id' => Str::uuid(), 'name' => 'live']);
        Tag::create(['id' => Str::uuid(), 'name' => 'sexuality']);
        Tag::create(['id' => Str::uuid(), 'name' => 'economics']);
        Tag::create(['id' => Str::uuid(), 'name' => 'lgbtq']);
        Tag::create(['id' => Str::uuid(), 'name' => 'christianity']);
        Tag::create(['id' => Str::uuid(), 'name' => 'islam']);
        Tag::create(['id' => Str::uuid(), 'name' => 'art']);
        Tag::create(['id' => Str::uuid(), 'name' => 'music']);
        Tag::create(['id' => Str::uuid(), 'name' => 'communism']);
        Tag::create(['id' => Str::uuid(), 'name' => 'socialism']);
        Tag::create(['id' => Str::uuid(), 'name' => 'children']);
        Tag::create(['id' => Str::uuid(), 'name' => 'entertainment']);
        Tag::create(['id' => Str::uuid(), 'name' => 'philosophy']);
        Tag::create(['id' => Str::uuid(), 'name' => 'ethics']);
        Tag::create(['id' => Str::uuid(), 'name' => 'morality']);
        Tag::create(['id' => Str::uuid(), 'name' => 'lifestyle']);
        Tag::create(['id' => Str::uuid(), 'name' => 'robotics']);
        Tag::create(['id' => Str::uuid(), 'name' => 'science']);
        Tag::create(['id' => Str::uuid(), 'name' => 'technology']);
        Tag::create(['id' => Str::uuid(), 'name' => 'engineering']);
        Tag::create(['id' => Str::uuid(), 'name' => 'software']);
        Tag::create(['id' => Str::uuid(), 'name' => 'software engineering']);
        Tag::create(['id' => Str::uuid(), 'name' => 'dance']);
        Tag::create(['id' => Str::uuid(), 'name' => 'sports']);
        Tag::create(['id' => Str::uuid(), 'name' => 'culture']);
        Tag::create(['id' => Str::uuid(), 'name' => 'business']);
        Tag::create(['id' => Str::uuid(), 'name' => 'investigative']);
        Tag::create(['id' => Str::uuid(), 'name' => 'documentary']);
        Tag::create(['id' => Str::uuid(), 'name' => 'community']);
        Tag::create(['id' => Str::uuid(), 'name' => 'food']);
        Tag::create(['id' => Str::uuid(), 'name' => 'drink']);
        Tag::create(['id' => Str::uuid(), 'name' => 'gymnastics']);
        Tag::create(['id' => Str::uuid(), 'name' => 'motivation']);
        Tag::create(['id' => Str::uuid(), 'name' => 'runway']);
        Tag::create(['id' => Str::uuid(), 'name' => 'modelling']);
        Tag::create(['id' => Str::uuid(), 'name' => 'pets']);
        Tag::create(['id' => Str::uuid(), 'name' => 'reality']);
        Tag::create(['id' => Str::uuid(), 'name' => 'nudity']);
        Tag::create(['id' => Str::uuid(), 'name' => 'violence']);
        Tag::create(['id' => Str::uuid(), 'name' => 'reality']);
        Tag::create(['id' => Str::uuid(), 'name' => 'debate']);
        Tag::create(['id' => Str::uuid(), 'name' => 'career']);
        Tag::create(['id' => Str::uuid(), 'name' => 'alternative']);
    }
}
