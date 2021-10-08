<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorysTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Category::create(['name' => 'for-you']);
        Category::create(['name' => 'trending']);
        Category::create(['name' => 'exclusive']);
        Category::create(['name' => 'action']);
        Category::create(['name' => 'religion']);
        Category::create(['name' => 'drama']);
        Category::create(['name' => 'kids']);
        Category::create(['name' => 'tv-series']);
    }
}
