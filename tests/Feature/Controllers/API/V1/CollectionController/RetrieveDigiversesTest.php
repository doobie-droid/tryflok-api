<?php

namespace Tests\Feature\Controllers\API\V1\CollectionController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class RetrieveDigiversesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_retrieve_all_digiverses_fails_with_invalid_parameters()
    {
        $response = $this->json('GET', '/api/v1/digiverses?page=ere');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);
        $response = $this->json('GET', '/api/v1/digiverses?page=-10');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);

        $response = $this->json('GET', '/api/v1/digiverses?limit=ere');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $response = $this->json('GET', '/api/v1/digiverses?limit=-30');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $max_limit_exceed = Constants\Constants::MAX_ITEMS_LIMIT + 1;
        $response = $this->json('GET', "/api/v1/digiverses?limit={$max_limit_exceed}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);

        $keyword_excess = Str::random(201);
        $response = $this->json('GET', "/api/v1/digiverses?keyword={$keyword_excess}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'keyword'
            ]
        ]);

        $response = $this->json('GET', '/api/v1/digiverses?tags=fdfr3-3434f-434,dfdrg-2323-frf');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'tags.0'
            ]
        ]);

        $response = $this->json('GET', '/api/v1/digiverses?creators=fdfr3-3434f-434,dfdrg-2323-frf');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'creators.0'
            ]
        ]);
    }

    public function test_unavailable_digiverses_do_not_get_returned()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);

        $tag = Models\Tag::factory()->create();
        Models\Collection::factory()
                        ->digiverse()
                        ->unavailable()
                        ->setTags([$tag])
                        ->count(4)
                        ->create();

        $response = $this->json('GET', '/api/v1/digiverses');
        $response->assertStatus(200);
        $this->assertTrue(empty($response->getData()->data->digiverses));
    }

    public function test_empty_digiverses_do_not_get_returned()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);

        $tag = Models\Tag::factory()->create();
        Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->count(4)
                        ->create();

        $response = $this->json('GET', '/api/v1/digiverses');
        $response->assertStatus(200);
        $this->assertTrue(empty($response->getData()->data->digiverses));
    }

    public function test_retrieve_digiverses_work_when_user_is_not_signed_in()
    {
        $tag = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
        Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->count(4)
                        ->create();
        $expected_response_structure = MockData\Digiverse::generateGetAllResponse();
        $expected_response_structure['data']['digiverse']['userables'] = [];
        $response = $this->json('GET', '/api/v1/digiverses');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ]
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 4);
    }

    public function test_retrieve_digiverses_work_when_user_is_signed_in()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $tag = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
        Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->count(4)
                        ->create();
        $expected_response_structure = MockData\Digiverse::generateGetAllResponse();
        $expected_response_structure['data']['digiverse']['userables'] = [];
        $response = $this->json('GET', '/api/v1/digiverses');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ]
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 4);
    }

    public function test_retrieve_digiverses_work_when_user_is_signed_in_and_has_paid()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $tag = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
        $digiverses = Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->count(4)
                        ->create();
        foreach ($digiverses as $digiverse) {
            Models\Userable::create([
                'user_id' => $user->id,
                'status' => 'available',
                'userable_type' => 'collection',
                'userable_id' => $digiverse->id,
            ]);
        }
        $expected_response_structure = MockData\Digiverse::generateGetAllResponse();
        $response = $this->json('GET', '/api/v1/digiverses');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ]
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 4);
    }
}
