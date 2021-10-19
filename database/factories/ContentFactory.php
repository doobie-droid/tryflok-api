<?php

namespace Database\Factories;

use App\Models\Content;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Content::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'title' => $this->faker->unique()->sentence(4),
            'description' => $this->faker->sentence(40),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['audio', 'video', 'pdf', 'newsletter', 'live-audio', 'live-video']),
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
            'views' => 0,
        ];
    }

    public function audio()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'audio',
            ];
        });
    }

    public function video()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'video',
            ];
        });
    }

    public function pdf()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'pdf',
            ];
        });
    }

    public function newsletter()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'newsletter',
            ];
        });
    }

    public function liveAudio()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'live-audio',
            ];
        });
    }

    public function liveVideo()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'live-video',
            ];
        });
    }

    public function unavailable()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_available' => 0,
            ];
        });
    }

    public function not_approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'approved_by_admin' => 0,
            ];
        });
    }
}
