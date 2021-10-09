<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;
class AssetsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //image asset
        Asset::create([
            'id' => '27c65768-c6c1-4875-872d-616c88062a7a',
            'url' => 'https://d14qbv6p3sxwfx.cloudfront.net/assets/NIDJyTFyaTcG1bDJ20211008/image/20211008GRZtNTbuzBJYK0FP.png',
            'storage_provider' => 'public-s3',
            'storage_provider_id' => 'assets/NIDJyTFyaTcG1bDJ20211008/image/20211008GRZtNTbuzBJYK0F',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        // video asset
        $video = Asset::create([
            'id' => '263ec55f-2bfc-4259-a66d-08ceed037f74',
            'url' => 'https://d14qbv6p3sxwfx.cloudfront.net/assets/14HcBXhCntwGYckG20211009/video/20211009GTAECUkDE4N8QTGc.m3u8',
            'storage_provider' => 'private-s3',
            'storage_provider_id' => 'assets/NIDJyTFyaTcG1bDJ20211008/image/20211008GRZtNTbuzBJYK0',
            'asset_type' => 'video',
            'mime_type' => 'application/vnd.apple.mpegurl',
        ]);
        $video->resolutions()->create([
            'storage_provider' => 'private-s3',
            'storage_provider_id' => 'assets/NIDJyTFyaTcG1bDJ20211008/image/20211008GRZtNTbuzBJYK0',
            'url' => 'https://d14qbv6p3sxwfx.cloudfront.net/assets/14HcBXhCntwGYckG20211009/video/20211009GTAECUkDE4N8QTGc_270p.m3u8',
            'resolution' => '270p',
        ]);
        //pdf asset
        Asset::create([
            'id' => '7cf2ea78-1efe-4ae7-a57d-b8b29a9ff7ae',
            'url' => 'https://d14qbv6p3sxwfx.cloudfront.net/assets/RNFsiR8zoQCjW4Q520211009/pdf/20211009X8suzvKsRtmyzZZe.pdf',
            'storage_provider' => 'private-s3',
            'storage_provider_id' => 'assets/NIDJyTFyaTcG1bDJ20211008/image/20211008GRZtNTbuzBJYK0F',
            'asset_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'encryption_key' => '9W+No9DbCEvfaIG5RCQjHApbrwN9u37+7AxxgjmP2GuTbXQMqsJBEM0rfgfgaREJXOoYoUfVITh1/HULsbyjJPNACKudMpj3fs2zSD0+sTufk5tj',
        ]);
        //audio asset
        Asset::create([
            'id' => '162102cb-0d83-40a2-9dc5-5e809c2b5bb3',
            'url' => 'https://d14qbv6p3sxwfx.cloudfront.net/assets/vIWtZfW7MkS6RebI20211008/audio/20211008qVw7wHk1MAtoPIwo.mp3',
            'storage_provider' => 'private-s3',
            'storage_provider_id' => 'assets/NIDJyTFyaTcG1bDJ20211008/image/20211008GRZtNTbuzBJYK0F',
            'asset_type' => 'audio',
            'mime_type' => 'audio/mpeg',
        ]);
    }
}
