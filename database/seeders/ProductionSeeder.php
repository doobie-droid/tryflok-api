<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RolesAndPermissionSeeder::class);
        $this->call(LocationsSeeder::class);
        $this->call(CategorysTableSeeder::class);
        $this->call(TagsTableSeeder::class);
        $this->call(LanguagesTableSeeder::class);
    }
}
