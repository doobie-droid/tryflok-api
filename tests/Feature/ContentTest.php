<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\User;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Benefactor;
use App\Models\Tag;
use App\Models\Asset;
use App\Models\Price;
use App\Constants\Roles;

class ContentTest extends TestCase
{
    use DatabaseTransactions;
    const STANDARD_CONTENT_RESPONSE = [
        'status_code',
        'message',
        'data' => [
            'content' => [
                'id',
                'title',
                'description',
                'owner' => [
                    'id',
                    'name',
                    'email',
                    'username',
                ],
                'type',
                'is_available',
                'approved_by_admin',
                'show_only_in_digiverses',
                'views',
                'ratings_count',
                'ratings_average',
                'cover' => [
                    'url',
                    'asset_type',
                ],
                'prices' => [
                    [
                        'id',
                        'amount',
                        'currency',
                        'interval',
                        'interval_amount'
                    ]
                ],
                'tags' => [
                    [
                        'id',
                        'type',
                        'name',
                    ]
                ],
                'assets' => [
                    [
                        'url',
                        'asset_type',
                        'encryption_key',
                        'resolutions'
                    ]
                ]
            ]
        ]
    ];

    const CONTENT_WITH_NO_ASSET_RESPONSE = [
        'status_code',
        'message',
        'data' => [
            'content' => [
                'id',
                'title',
                'description',
                'owner' => [
                    'id',
                    'name',
                    'email',
                    'username',
                ],
                'type',
                'is_available',
                'approved_by_admin',
                'show_only_in_digiverses',
                'views',
                'ratings_count',
                'ratings_average',
                'cover' => [
                    'url',
                    'asset_type',
                ],
                'prices' => [
                    [
                        'id',
                        'amount',
                        'currency',
                        'interval',
                        'interval_amount'
                    ]
                ],
                'tags' => [
                    [
                        'id',
                        'type',
                        'name',
                    ]
                ],
                'assets'
            ]
        ]
    ];

    public function test_content_is_not_created_with_invalid_inputs()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();
        $coverAsset = Asset::factory()->create();
        $audioAsset = Asset::factory()->audio()->create();
        $pdfAsset = Asset::factory()->pdf()->create();
        $completeRequest = [
            'title' => 'A content',
            'description' => 'Content description',
            'type' => 'audio',
            'asset_id' => $audioAsset->id,
            'digiverse_id' => $digiverse->id,
            'price' => [
                'amount' => 10,
                'interval' => 'one-off',
                'interval_amount' => 1,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff', 
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        // when no digiverse id is supplied
        $request = $completeRequest;
        $request['digiverse_id'] = '0a14760d-1d41-45aa-a820-87d6dc35f7fz';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when invalid digiverse id is supplied
        $request = $completeRequest;
        $request['digiverse_id'] = '';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when title is ommited
        $request = $completeRequest;
        $request['title'] = '';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when type is omitted
        $request = $completeRequest;
        $request['type'] = '';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when type is not valid
        $request = $completeRequest;
        $request['type'] = 'apolo';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when cover is omitted
        $request = $completeRequest;
        $request['cover']['asset_id'] = '';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when wrong asset is ommitted for audio
        $request = $completeRequest;
        $request['cover']['asset_id'] = '';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when wrong asset is used for cover
        $request = $completeRequest;
        $request['cover']['asset_id'] = $audioAsset->id;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when asset type does not match content type
        $request = $completeRequest;
        $request['asset_id'] = $pdfAsset->id;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
    }

    public function test_only_one_newsletter_per_digiverse()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);

        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->hasAttached(
            Content::factory()->state([
                'type' => 'newsletter'
            ]),
            [
                'id' => Str::uuid(),
            ]
        )
        ->create();
        $coverAsset = Asset::factory()->create();
        $completeRequest = [
            'title' => 'A content',
            'description' => 'Content description',
            'type' => 'newsletter',
            'digiverse_id' => $digiverse->id,
            'price' => [
                'amount' => 10,
                'interval' => 'one-off',
                'interval_amount' => 1,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff', 
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $completeRequest);
        $response->assertStatus(400);
    }

    public function test_video_content_gets_created()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Asset::factory()->create();
        $videoAsset = Asset::factory()->video()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'video',
            'asset_id' => $videoAsset->id,
            'digiverse_id' => $digiverse->id,
            'price' => [
                'amount' => 10,
                'interval' => 'one-off',
                'interval_amount' => 1,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff', 
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)->assertJsonStructure(self::STANDARD_CONTENT_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'video',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_digiverses' => 1,
            'views' => 0,
        ]);
        $content = Content::where('title', $request['title'])->first();
        // content is attached to collection
        $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);
        // validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][1])->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate asset was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $videoAsset->id,
            'purpose' => 'content-asset',
        ]);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $request['price']['amount'],
            'interval' => $request['price']['interval'],
            'interval_amount' => $request['price']['interval_amount'],
            'currency' => 'USD',
        ]);
        $this->assertTrue($content->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'content',
            'benefactable_id' => $content->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
    }

    public function test_audio_content_gets_created()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Asset::factory()->create();
        $audioAsset = Asset::factory()->audio()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'audio',
            'asset_id' => $audioAsset->id,
            'digiverse_id' => $digiverse->id,
            'price' => [
                'amount' => 10,
                'interval' => 'one-off',
                'interval_amount' => 1,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff', 
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(self::STANDARD_CONTENT_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'audio',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_digiverses' => 1,
            'views' => 0,
        ]);
        $content = Content::where('title', $request['title'])->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][1])->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate asset was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $audioAsset->id,
            'purpose' => 'content-asset',
        ]);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $request['price']['amount'],
            'interval' => $request['price']['interval'],
            'interval_amount' => $request['price']['interval_amount'],
            'currency' => 'USD',
        ]);
        $this->assertTrue($content->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'content',
            'benefactable_id' => $content->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
    }

    public function test_pdf_content_gets_created()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Asset::factory()->create();
        $pdfAsset = Asset::factory()->pdf()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'pdf',
            'asset_id' => $pdfAsset->id,
            'digiverse_id' => $digiverse->id,
            'price' => [
                'amount' => 10,
                'interval' => 'one-off',
                'interval_amount' => 1,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff', 
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(self::STANDARD_CONTENT_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'pdf',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_digiverses' => 1,
            'views' => 0,
        ]);
        $content = Content::where('title', $request['title'])->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][1])->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate asset was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $pdfAsset->id,
            'purpose' => 'content-asset',
        ]);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $request['price']['amount'],
            'interval' => $request['price']['interval'],
            'interval_amount' => $request['price']['interval_amount'],
            'currency' => 'USD',
        ]);
        $this->assertTrue($content->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'content',
            'benefactable_id' => $content->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
    }

    public function test_newsletter_content_gets_created()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Asset::factory()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'newsletter',
            'digiverse_id' => $digiverse->id,
            'price' => [
                'amount' => 0,
                'interval' => 'one-off',
                'interval_amount' => 1,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff', 
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(self::CONTENT_WITH_NO_ASSET_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'newsletter',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_digiverses' => 1,
            'views' => 0,
        ]);
        $content = Content::where('title', $request['title'])->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][1])->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $request['price']['amount'],
            'interval' => $request['price']['interval'],
            'interval_amount' => $request['price']['interval_amount'],
            'currency' => 'USD',
        ]);
        $this->assertTrue($content->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'content',
            'benefactable_id' => $content->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
    }

    public function test_live_audio_content_gets_created()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Asset::factory()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'live-audio',
            'digiverse_id' => $digiverse->id,
            'price' => [
                'amount' => 0,
                'interval' => 'one-off',
                'interval_amount' => 1,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff', 
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(self::CONTENT_WITH_NO_ASSET_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'live-audio',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_digiverses' => 1,
            'views' => 0,
        ]);
        $content = Content::where('title', $request['title'])->first();

        // content is attached to collection
        $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][1])->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $request['price']['amount'],
            'interval' => $request['price']['interval'],
            'interval_amount' => $request['price']['interval_amount'],
            'currency' => 'USD',
        ]);
        $this->assertTrue($content->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'content',
            'benefactable_id' => $content->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
    }

    public function test_live_video_content_gets_created()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Asset::factory()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'live-video',
            'digiverse_id' => $digiverse->id,
            'price' => [
                'amount' => 0,
                'interval' => 'one-off',
                'interval_amount' => 1,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff', 
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(self::CONTENT_WITH_NO_ASSET_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'live-video',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_digiverses' => 1,
            'views' => 0,
        ]);
        $content = Content::where('title', $request['title'])->first();

        // content is attached to collection
        $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][1])->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $request['price']['amount'],
            'interval' => $request['price']['interval'],
            'interval_amount' => $request['price']['interval_amount'],
            'currency' => 'USD',
        ]);
        $this->assertTrue($content->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'content',
            'benefactable_id' => $content->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
    }
}
