<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;

class ReviewsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Review::create([
            'user_id' => 3,
            'rating' => 4,
            'comment' => 'superb',
            'reviewable_type' => 'content',
            'reviewable_id' => 1,
        ]);

        Review::create([
            'user_id' => 4,
            'rating' => 5,
            'comment' => 'great',
            'reviewable_type' => 'content',
            'reviewable_id' => 1,
        ]);

        //
        Review::create([
            'user_id' => 3,
            'rating' => 4,
            'comment' => 'superb',
            'reviewable_type' => 'content',
            'reviewable_id' => 2,
        ]);

        Review::create([
            'user_id' => 4,
            'rating' => 5,
            'comment' => 'great',
            'reviewable_type' => 'content',
            'reviewable_id' => 2,
        ]);

        //
        Review::create([
            'user_id' => 3,
            'rating' => 4,
            'comment' => 'superb',
            'reviewable_type' => 'content',
            'reviewable_id' => 3,
        ]);

        Review::create([
            'user_id' => 4,
            'rating' => 5,
            'comment' => 'great',
            'reviewable_type' => 'content',
            'reviewable_id' => 3,
        ]);

        //
        Review::create([
            'user_id' => 3,
            'rating' => 4,
            'comment' => 'superb',
            'reviewable_type' => 'collection',
            'reviewable_id' => 1,
        ]);

        Review::create([
            'user_id' => 4,
            'rating' => 5,
            'comment' => 'great',
            'reviewable_type' => 'collection',
            'reviewable_id' => 1,
        ]);
    }
}
