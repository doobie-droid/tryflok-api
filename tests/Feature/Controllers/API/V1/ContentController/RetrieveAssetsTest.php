<?php

namespace Tests\Feature\Controllers\API\V1\ContentController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class RetrieveAssetsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_asset_gets_returned_for_free_content()
    {
        $digiverse = Models\Collection::factory()->digiverse()->create();
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setTags([Models\Tag::factory()->create()])
        ->create();

        $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetAssetsResponse());
    }

    public function test_asset_is_not_returned_for_paid_content_when_user_has_not_paid()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);

        $digiverse = Models\Collection::factory()->digiverse()->create();
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setPriceAmount(100)
        ->setTags([Models\Tag::factory()->create()])
        ->create();

        $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets");
        $response->assertStatus(400);
        $this->assertEquals($response->getData()->message, 'You are not permitted to view the assets of this content');
    }

    public function test_asset_is_not_returned_for_free_content_with_paid_ancestor_when_user_has_not_paid()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);

        $digiverse = Models\Collection::factory()
            ->digiverse()
            ->setPriceAmount(100)
            ->create();
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setTags([Models\Tag::factory()->create()])
        ->create();

        $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets");
        $response->assertStatus(400);
        $this->assertEquals($response->getData()->message, 'You are not permitted to view the assets of this content');
    }

    public function test_asset_is_returned_for_paid_content_when_user_has_paid()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);

        $digiverse = Models\Collection::factory()->digiverse()->create();
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setPriceAmount(100)
        ->setTags([Models\Tag::factory()->create()])
        ->create();

        Models\Userable::create([
            'user_id' => $user->id,
            'status' => 'available',
            'userable_type' => 'content',
            'userable_id' => $content->id,
        ]);

        $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetAssetsResponse());
    }

    public function test_asset_is_returned_for_paid_content_with_paid_ancestor_when_user_has_paid_directly()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);

        $digiverse = Models\Collection::factory()
            ->digiverse()
            ->setPriceAmount(100)
            ->create();
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setPriceAmount(100)
        ->setTags([Models\Tag::factory()->create()])
        ->create();

        Models\Userable::create([
            'user_id' => $user->id,
            'status' => 'available',
            'userable_type' => 'content',
            'userable_id' => $content->id,
        ]);

        $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetAssetsResponse());
    }

    public function test_asset_is_returned_for_free_content_with_paid_ancestor_when_user_has_paid_via_ancestor()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);

        $digiverse = Models\Collection::factory()
            ->digiverse()
            ->setPriceAmount(100)
            ->create();
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setTags([Models\Tag::factory()->create()])
        ->create();

        Models\Userable::create([
            'user_id' => $user->id,
            'status' => 'available',
            'userable_type' => 'collection',
            'userable_id' => $digiverse->id,
        ]);

        $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetAssetsResponse());
    }

    public function test_asset_is_returned_for_paid_content_with_paid_ancestor_when_user_has_paid_via_ancestor()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);

        $digiverse = Models\Collection::factory()
            ->digiverse()
            ->setPriceAmount(100)
            ->create();
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setPriceAmount(100)
        ->setTags([Models\Tag::factory()->create()])
        ->create();

        Models\Userable::create([
            'user_id' => $user->id,
            'status' => 'available',
            'userable_type' => 'collection',
            'userable_id' => $digiverse->id,
        ]);

        $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetAssetsResponse());
    }
}
