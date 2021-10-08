<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RolesAndPermissionSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(LocationsSeeder::class);
        $this->call(CategorysTableSeeder::class);
        $this->call(TagsTableSeeder::class);
        $this->call(LanguagesTableSeeder::class);
        // $this->call(ContentSeeder::class);
        // $this->call(ReviewsTableSeeder::class);
    }
}
