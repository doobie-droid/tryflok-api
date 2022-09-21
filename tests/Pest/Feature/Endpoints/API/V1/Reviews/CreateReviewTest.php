<?php 

use App\Models;

beforeEach(function()
{
    $this->user = Models\User::factory()->create();
    $this->be($this->user);

});

test('review is not created with invalid inputs', function()
{
            $content = Models\Content::factory()->create();

            /**
        * when an invalid type is supplied
        */
            $request = [
                'type' => 'sdsd',
                'id' => $content->id,
                'rating' => 3,
            ];
            $response = $this->json('POST', '/api/v1/reviews', $request);
            $response->assertStatus(400);

            /**
        * when no id is supplied
        */
            $request = [
                'type' => 'content',
                'rating' => 3,
            ];
            $response = $this->json('POST', '/api/v1/reviews', $request);
            $response->assertStatus(400);

            /**
        * when an invalid rating is supplied
        */
            $request = [
                'type' => 'content',
                'id' => $content->id,
                'rating' => -3,
            ];
            $response = $this->json('POST', '/api/v1/reviews', $request);
            $response->assertStatus(400);

            $request = [
                'type' => 'content',
                'id' => $content->id,
                'rating' => 0,
            ];
            $response = $this->json('POST', '/api/v1/reviews', $request);
            $response->assertStatus(400);

            $request = [
                'type' => 'content',
                'id' => $content->id,
                'rating' => 6,
            ];
            $response = $this->json('POST', '/api/v1/reviews', $request);
            $response->assertStatus(400);

            /**
        * when both rating and comment are not supplied
        */
            $request = [
                'type' => 'content',
                'id' => $content->id,
            ];
            $response = $this->json('POST', '/api/v1/reviews', $request);
            $response->assertStatus(400);
});

test('create content review works', function()
{
        $content = Models\Content::factory()->create();

        /**
    * when only rating is supplied
    */
        $request = [
            'type' => 'content',
            'id' => $content->id,
            'rating' => 3,
        ];
        $response = $this->json('POST', '/api/v1/reviews', $request);
        $response->assertStatus(200);

        $this->assertDatabaseHas('reviews', [
            'reviewable_type' => 'content',
            'reviewable_id' => $content->id,
            'rating' => $request['rating'],
        ]);

        /**
    * when only comment is supplied
    */
        $request = [
            'type' => 'content',
            'id' => $content->id,
            'comment' => 'it was good',
        ];
        $response = $this->json('POST', '/api/v1/reviews', $request);
        $response->assertStatus(200);

        $this->assertDatabaseHas('reviews', [
            'reviewable_type' => 'content',
            'reviewable_id' => $content->id,
            'comment' => $request['comment'],
        ]);
});

test('create collection review works', function()
{
    $collection = Models\Collection::factory()->create();

    /**
* when only rating is supplied
*/
    $request = [
        'type' => 'collection',
        'id' => $collection->id,
        'rating' => 3,
    ];
    $response = $this->json('POST', '/api/v1/reviews', $request);
    $response->assertStatus(200);

    $this->assertDatabaseHas('reviews', [
        'reviewable_type' => 'collection',
        'reviewable_id' => $collection->id,
        'rating' => $request['rating'],
    ]);

     /**
* when only comment is supplied
*/
     $request = [
        'type' => 'collection',
        'id' => $collection->id,
        'comment' => 'it was good',
     ];
     $response = $this->json('POST', '/api/v1/reviews', $request);
     $response->assertStatus(200);

     $this->assertDatabaseHas('reviews', [
        'reviewable_type' => 'collection',
        'reviewable_id' => $collection->id,
        'comment' => $request['comment'],
     ]);
});

test('create review works', function()
{
        $review = Models\Review::factory()->create();

        /**
    * when only rating is supplied
    */
        $request = [
            'type' => 'review',
            'id' => $review->id,
            'rating' => 3,
        ];
        $response = $this->json('POST', '/api/v1/reviews', $request);
        $response->assertStatus(200);

        $this->assertDatabaseHas('reviews', [
            'reviewable_type' => 'review',
            'reviewable_id' => $review->id,
            'rating' => $request['rating'],
        ]);

        /**
    * when only comment is supplied
    */
        $request = [
            'type' => 'review',
            'id' => $review->id,
            'comment' => 'it was good',
        ];
        $response = $this->json('POST', '/api/v1/reviews', $request);
        $response->assertStatus(200);

        $this->assertDatabaseHas('reviews', [
            'reviewable_type' => 'review',
            'reviewable_id' => $review->id,
            'comment' => $request['comment'],
        ]);
});