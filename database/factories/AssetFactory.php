<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'url' => 'https://d14qbv6p3sxwfx.cloudfront.net/assets/NIDJyTFyaTcG1bDJ20211008/image/20211008GRZtNTbuzBJYK0FP.png',
            'storage_provider' => 'public-s3',
            'storage_provider_id' => Str::random(10),
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ];
    }

    public function video()
    {
        return $this->state(function (array $attributes) {
            return [
                'url' => 'https://d14qbv6p3sxwfx.cloudfront.net/assets/14HcBXhCntwGYckG20211009/video/20211009GTAECUkDE4N8QTGc.m3u8',
                'storage_provider' => 'private-s3',
                'asset_type' => 'video',
                'mime_type' => 'application/vnd.apple.mpegurl',
            ];
        });
    }

    public function pdf()
    {
        return $this->state(function (array $attributes) {
            return [
                'url' => 'https://d14qbv6p3sxwfx.cloudfront.net/assets/RNFsiR8zoQCjW4Q520211009/pdf/20211009X8suzvKsRtmyzZZe.pdf',
                'storage_provider' => 'private-s3',
                'asset_type' => 'pdf',
                'mime_type' => 'application/pdf',
                'encryption_key' => '9W+No9DbCEvfaIG5RCQjHApbrwN9u37+7AxxgjmP2GuTbXQMqsJBEM0rfgfgaREJXOoYoUfVITh1/HULsbyjJPNACKudMpj3fs2zSD0+sTufk5tj',
            ];
        });
    }

    public function audio()
    {
        return $this->state(function (array $attributes) {
            return [
                'url' => 'https://d14qbv6p3sxwfx.cloudfront.net/assets/vIWtZfW7MkS6RebI20211008/audio/20211008qVw7wHk1MAtoPIwo.mp3',
                'storage_provider' => 'private-s3',
                'asset_type' => 'audio',
                'mime_type' => 'audio/mpeg',
            ];
        });
    }
}
