<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Tag::create(['name' => 'horror']);
        Tag::create(['name' => 'crypto']);
        Tag::create(['name' => 'fashion']);
        Tag::create(['name' => 'adventure']);
        Tag::create(['name' => 'educative']);
        Tag::create(['name' => 'african']);
    }
}
