<?php

namespace Database\Factories;

use App\Constants\Roles;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /** @var User */ 
    private $user;

    /** @var Asset */
    private $profile_picture;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->unique()->username . date('YmdHis'),
            'bio' => $this->faker->sentence(20),
            'dob' => now(),
            'email_verified' => 1,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'referral_id' => strtoupper(Str::random(6)).'-'.date('Ymd'),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (User $user) {
           $this->user = $user;
           $this->user->assignRole(Roles::USER);
           $this->generateProfilePicture();
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified' => 0,
            ];
        });
    }

    private function generateProfilePicture(): void
    {
        $previousPicture = $this->user->profile_picture()->first();
        if (! is_null($previousPicture)) {
            $this->user->profile_picture()->detach($previousPicture->id);
            $previousPicture->forceDelete();
        }
        
        $this->profile_picture = $this->profile_picture ?? Asset::factory()->create();
        $this->user->profile_picture()->attach($this->profile_picture->id, [
            'id' => Str::uuid(),
            'purpose' => 'profile-picture',
        ]);
    }
}
