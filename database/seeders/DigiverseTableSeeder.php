<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Collection;
use App\Models\Category;
use App\Models\Price;
use App\Models\User;
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
    }
}
