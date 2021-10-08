<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RolesAndPermissionSeeder::class);
        $this->call(ProdUsersSeeder::class);
        $this->call(LocationsSeeder::class);
        $this->call(CategorysTableSeeder::class);
        $this->call(TagsTableSeeder::class);
        $this->call(LanguagesTableSeeder::class);
    }
}
