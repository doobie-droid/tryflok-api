<?php

namespace Tests\Feature\Controllers\API\V1\CollectionController;

use App\Constants\Roles;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MockData;
use Tests\TestCase;

class RetrieveSingleDigiverseTest extends TestCase
{
    use DatabaseTransactions;

    public function test_retrieve_single_digiverse_works_when_user_is_not_signed_in()
    {
        $tag = Models\Tag::factory()->create();
        $digiverse = Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->create();
        $expected_response_structure = MockData\Digiverse::STANDARD_RESPONSE;
        $expected_response_structure['data']['digiverse']['userables'] = [];
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}");
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);
        $userables = $response->getData()->data->digiverse->userables;
        $this->assertEquals($userables, []);
    }

    public function test_retrieve_single_digiverse_returns_correct_content_types_available()
    {
        $tag = Models\Tag::factory()->create();
        $videoContent = Models\Content::factory()->video()->create();
        $audioContent = Models\Content::factory()->audio()->create();
        $digiverse = Models\Collection::factory()
                        ->digiverse()
                        ->setContents([$videoContent, $audioContent])
                        ->setTags([$tag])
                        ->create();
        $expected_response_structure = MockData\Digiverse::STANDARD_RESPONSE;
        $expected_response_structure['data']['digiverse']['userables'] = [];
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}");
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);
        $content_types_available = $response->getData()->data->digiverse->content_types_available;
        $this->assertTrue(count($content_types_available) === 2);
        $this->assertTrue(in_array('video', $content_types_available));
        $this->assertTrue(in_array('audio', $content_types_available));
    }

    public function test_retrieve_single_digiverse_works_when_user_has_not_paid_for_digiverse()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $tag = Models\Tag::factory()->create();

        $digiverse = Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->create();
        Models\Userable::create([
            'user_id' => $user->id,
            'status' => 'subscription-ended',
            'userable_type' => 'collection',
            'userable_id' => $digiverse->id,
        ]);
        $expected_response_structure = MockData\Digiverse::STANDARD_RESPONSE;
        $expected_response_structure['data']['digiverse']['userables'] = [];
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}");
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);
        $userables = $response->getData()->data->digiverse->userables;
        $this->assertEquals($userables, []);
    }

    public function test_retrieve_single_digiverse_works_when_user_has_paid_for_digiverse()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $tag = Models\Tag::factory()->create();

        $digiverse = Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->create();
        Models\Userable::create([
            'user_id' => $user->id,
            'status' => 'available',
            'userable_type' => 'collection',
            'userable_id' => $digiverse->id,
        ]);
        $expected_response_structure = MockData\Digiverse::STANDARD_RESPONSE;
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}");
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);
        $userables = $response->getData()->data->digiverse->userables;
        $this->assertFalse(empty($userables));
        $this->assertTrue($userables[0]->user_id === $user->id);
    }
}
