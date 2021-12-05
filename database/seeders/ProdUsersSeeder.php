<?php

namespace Database\Seeders;

use App\Constants\Roles;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProdUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fanan = User::create([
            'name' => 'Fanan Dala',
            'email' => 'fanan@akiddie.com.ng',
            'username' => 'the_cocoreidh',
            'password' => Hash::make('admin123'),
            'referral_id' => 'FANAN-210506',
        ]);
        $fanan->assignRole(Roles::SUPER_ADMIN);
        $fanan->assignRole(Roles::ADMIN);

        $dominic = User::create([
            'name' => 'Dominic Dominic',
            'email' => 'dominic@akiddie.com.ng',
            'username' => 'oke_alusi',
            'password' => Hash::make('admin123'),
            'referral_id' => 'DOMINIC-210506',
        ]);
        $dominic->assignRole(Roles::ADMIN);

        $akiddie = User::create([
            'name' => 'Akiddie',
            'email' => 'contact@akiddie.com.ng',
            'username' => 'akiddie',
            'password' => Hash::make('admin123'),
            'referral_id' => 'AKIDDIE-210506',
        ]);
        $akiddie->assignRole(Roles::USER);

        $tolu = User::create([
            'name' => 'Tolu Wojuola',
            'email' => 'tolu@akiddie.com.ng',
            'username' => 'wojay',
            'password' => Hash::make('admin123'),
            'referral_id' => 'WOJAY-210506',
        ]);
        $tolu->assignRole(Roles::ADMIN);

    }
}
