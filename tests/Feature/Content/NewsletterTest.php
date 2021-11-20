<?php

namespace Tests\Feature\Content;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Benefactor;
use App\Models\Tag;
use App\Models\Asset;
use App\Models\Price;
use App\Constants\Roles;
use Tests\MockData\Content as ContentMock;

class NewsletterTest extends TestCase
{
    public function test_create_newletter_works()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->hasAttached(
            Content::factory()->state([
                'type' => 'newsletter',
                'user_id' => $user->id,
            ]),
            [
                'id' => Str::uuid(),
            ]
        )
        ->create();

        $newsletter = $digiverse->contents()->where('type', 'newsletter')->first();
        $request = [
            'title' => 'First Issue ' . date('YmdHis'),
            'is_available' => 1,
            'description' => <<<HTML
            <p><img style="display: block; margin-left: auto; margin-right: auto;" title="Tiny Logo" src="https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png" alt="TinyMCE Logo" width="128" height="128" /></p>
            <h2 style="text-align: center;">Welcome to the TinyMCE editor demo!</h2>
          
            <h2>Got questions or need help?</h2>
          
            <ul>
              <li>Our <a href="https://www.tiny.cloud/docs/">documentation</a> is a great resource for learning how to configure TinyMCE.</li>
              <li>Have a specific question? Try the <a href="https://stackoverflow.com/questions/tagged/tinymce" target="_blank" rel="noopener"><code>tinymce</code> tag at Stack Overflow</a>.</li>
              <li>We also offer enterprise grade support as part of <a href="https://www.tiny.cloud/pricing">TinyMCE premium plans</a>.</li>
            </ul>
          
            <h2>A simple table to play with</h2>
          
            <table style="border-collapse: collapse; width: 100%;" border="1">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Cost</th>
                  <th>Really?</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>TinyMCE</td>
                  <td>Free</td>
                  <td>YES!</td>
                </tr>
                <tr>
                  <td>Plupload</td>
                  <td>Free</td>
                  <td>YES!</td>
                </tr>
              </tbody>
            </table>
          
            <h2>Found a bug?</h2>
          
            <p>
              If you think you have found a bug please create an issue on the <a href="https://github.com/tinymce/tinymce/issues">GitHub repo</a> to report it to the developers.
            </p>
          
            <h2>Finally ...</h2>
          
            <p>
              Don't forget to check out our other product <a href="http://www.plupload.com" target="_blank">Plupload</a>, your ultimate upload solution featuring HTML5 upload support.
            </p>
            <p>
              Thanks for supporting TinyMCE! We hope it helps you and your users create great content.<br>All the best from the TinyMCE team.
            </p>
            HTML,
        ];
        $response = $this->json('POST', "/api/v1/contents/{$newsletter->id}/issue", $request);
        $response->assertStatus(200)->assertJsonStructure(ContentMock::STANDARD_ISSUE_RESPONSE);
        $this->assertDatabaseHas('content_issues', [
            'title' => $request['title'],
            'description' => str_replace("\n", "", $request['description']),
            'content_id' => $newsletter->id,
            'is_available' => 1,
            'views' => 0,
        ]);
    }
}
