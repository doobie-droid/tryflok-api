<?php

namespace Database\Factories;

use App\Models\AssetResolution;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetResolutionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AssetResolution::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'storage_provider' => 'private-s3',
            'storage_provider_id' => Str::random(10),
            'url' => 'https://d14qbv6p3sxwfx.cloudfront.net/assets/14HcBXhCntwGYckG20211009/video/20211009GTAECUkDE4N8QTGc_270p.m3u8',
            'resolution' => '270p',
        ];
    }
}
