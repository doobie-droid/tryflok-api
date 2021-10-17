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
        Tag::create(['id' => '0e14760d-1d41-45aa-a820-87d6dc35f7ff', 'name' => 'horror']);
        Tag::create(['id' => '120566de-0361-4d66-b458-321d4ede62a9', 'name' => 'crypto']);
        Tag::create(['id' => '1693fbe5-e3a4-4338-9fc8-e305b3446d6e', 'name' => 'fashion']);
        Tag::create(['id' => '2186c1d6-fea2-4746-ac46-0e4f445f7c9e', 'name' => 'adventure']);
        Tag::create(['id' => '2743557a-dfb5-44e3-9537-23f2f5cc9957', 'name' => 'educative']);
        Tag::create(['id' => '431b915f-e983-48d0-9c82-10e55e0d06d7', 'name' => 'african']);
    }
}
