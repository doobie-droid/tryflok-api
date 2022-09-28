<?php

namespace Database\Seeders;

use App\Constants\Roles;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Tests\MockData\User as UserMock;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $superAdmin = User::create([
            'name' => UserMock::SEEDED_SUPER_ADMIN['name'],
            'email' => UserMock::SEEDED_SUPER_ADMIN['email'],
            'username' => UserMock::SEEDED_SUPER_ADMIN['username'],
            'password' => Hash::make(UserMock::SEEDED_SUPER_ADMIN['password']),
            'referral_id' => UserMock::SEEDED_SUPER_ADMIN['referral_id'],
        ]);

        $superAdmin->assignRole(Roles::SUPER_ADMIN);

        $admin = User::create([
            'name' => UserMock::SEEDED_ADMIN['name'],
            'email' => UserMock::SEEDED_ADMIN['email'],
            'username' => UserMock::SEEDED_ADMIN['username'],
            'password' => Hash::make(UserMock::SEEDED_ADMIN['password']),
            'referral_id' => UserMock::SEEDED_ADMIN['referral_id'],
        ]);

        $admin->assignRole(Roles::ADMIN);

        $user = User::create([
            'name' => UserMock::SEEDED_USER['name'],
            'email' => UserMock::SEEDED_USER['email'],
            'username' => UserMock::SEEDED_USER['username'],
            'password' => Hash::make(UserMock::SEEDED_USER['password']),
            'referral_id' => UserMock::SEEDED_USER['referral_id'],
        ]);
        $user->assignRole(Roles::USER);

        $creator = User::create([
            'referrer_id' => 5,//creator 2
            'name' => UserMock::SEEDED_CREATOR['name'],
            'email' => UserMock::SEEDED_CREATOR['email'],
            'username' => UserMock::SEEDED_CREATOR['username'],
            'password' => Hash::make(UserMock::SEEDED_CREATOR['password']),
            'referral_id' => UserMock::SEEDED_CREATOR['referral_id'],
        ]);
        $creator->assignRole(Roles::USER);

        $creator2 = User::create([
            'name' => UserMock::SEEDED_CREATOR_2['name'],
            'email' => UserMock::SEEDED_CREATOR_2['email'],
            'username' => UserMock::SEEDED_CREATOR_2['username'],
            'password' => Hash::make(UserMock::SEEDED_CREATOR_2['password']),
            'referral_id' => UserMock::SEEDED_CREATOR_2['referral_id'],
        ]);
        $creator2->assignRole(Roles::USER);

        $creator3 = User::create([
            'name' => UserMock::SEEDED_CREATOR_3['name'],
            'email' => UserMock::SEEDED_CREATOR_3['email'],
            'username' => UserMock::SEEDED_CREATOR_3['username'],
            'password' => Hash::make(UserMock::SEEDED_CREATOR_3['password']),
            'referral_id' => UserMock::SEEDED_CREATOR_3['referral_id'],
        ]);
        $creator3->assignRole(Roles::USER);

        $creator4 = User::create([
            'name' => UserMock::SEEDED_CREATOR_4['name'],
            'email' => UserMock::SEEDED_CREATOR_4['email'],
            'username' => UserMock::SEEDED_CREATOR_4['username'],
            'password' => Hash::make(UserMock::SEEDED_CREATOR_4['password']),
            'referral_id' => UserMock::SEEDED_CREATOR_4['referral_id'],
        ]);
        $creator4->assignRole(Roles::USER);
    }
}
