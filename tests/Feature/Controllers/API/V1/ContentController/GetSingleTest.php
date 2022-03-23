<?php

namespace Tests\Feature\Controllers\API\V1\ContentController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class GetSingleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_retrieve_single_content_works_when_user_is_not_signed_in()
    {
        $content = Models\Content::factory()->setTags([Models\Tag::factory()->create()])->create();
        $response = $this->json('GET', "/api/v1/contents/{$content->id}");
        $response->assertStatus(200)->assertJsonStructure(MockData\Content::generateGetSingleContentResponse());
        $userables = $response->getData()->data->content->userables;
        $this->assertEquals($userables, []);
        $access_through_ancestors = $response->getData()->data->content->access_through_ancestors;
        $this->assertEquals($access_through_ancestors, []);
    }

    public function test_retrieve_single_content_works_when_user_is_signed_in_and_has_not_paid_for_content()
    {
        $user = Models\User::factory()->create();
        $this->be($user);

        $content = Models\Content::factory()->setTags([Models\Tag::factory()->create()])->create();
        $digiverse = $content->collections()->first();
        Models\Userable::create([
            'user_id' => $user->id,
            'status' => 'subscription-ended',
            'userable_type' => 'collection',
            'userable_id' => $digiverse->id,
        ]);
        Models\Userable::create([
            'user_id' => $user->id,
            'status' => 'unavailable',
            'userable_type' => 'content',
            'userable_id' => $content->id,
        ]);
        $response = $this->json('GET', "/api/v1/contents/{$content->id}");
        $response->assertStatus(200)->assertJsonStructure(MockData\Content::generateGetSingleContentResponse());
        $userables = $response->getData()->data->content->userables;
        $this->assertEquals($userables, []);
        $access_through_ancestors = $response->getData()->data->content->access_through_ancestors;
        $this->assertEquals($access_through_ancestors, []);
    }

    public function test_retrieve_single_content_works_when_user_is_signed_in_and_has_paid_for_content_directly()
    {
        $user = Models\User::factory()->create();
        $this->be($user);

        $content = Models\Content::factory()->setTags([Models\Tag::factory()->create()])->create();
        $digiverse = $content->collections()->first();
        Models\Userable::create([
            'user_id' => $user->id,
            'status' => 'subscription-ended',
            'userable_type' => 'collection',
            'userable_id' => $digiverse->id,
        ]);
        Models\Userable::create([
            'user_id' => $user->id,
            'status' => 'available',
            'userable_type' => 'content',
            'userable_id' => $content->id,
        ]);
        $response = $this->json('GET', "/api/v1/contents/{$content->id}");
        $response->assertStatus(200)->assertJsonStructure(MockData\Content::generateGetSingleContentResponse());
        $userables = $response->getData()->data->content->userables;
        $this->assertFalse(empty($userables));
        $this->assertTrue($userables[0]->user_id === $user->id);
        $access_through_ancestors = $response->getData()->data->content->access_through_ancestors;
        $this->assertEquals($access_through_ancestors, []);
    }
    
    public function test_retrieve_single_content_works_when_user_is_signed_in_and_has_paid_for_content_via_ancestor()
    {
        $user = Models\User::factory()->create();
        $this->be($user);

        $content = Models\Content::factory()->setTags([Models\Tag::factory()->create()])->create();
        $digiverse = $content->collections()->first();
        Models\Userable::create([
            'user_id' => $user->id,
            'status' => 'available',
            'userable_type' => 'collection',
            'userable_id' => $digiverse->id,
        ]);

        $response = $this->json('GET', "/api/v1/contents/{$content->id}");
        $response->assertStatus(200)->assertJsonStructure(MockData\Content::generateGetSingleContentResponse());
        $userables = $response->getData()->data->content->userables;
        $this->assertEquals($userables, []);
        $access_through_ancestors = $response->getData()->data->content->access_through_ancestors;
        $this->assertFalse(empty($access_through_ancestors));
    }
}
