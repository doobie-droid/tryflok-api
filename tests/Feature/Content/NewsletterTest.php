<?php

namespace Tests\Feature\Content;

use App\Constants\Roles;
use App\Models\Collection;
use App\Models\Content;
use App\Models\ContentIssue;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\MockData\Content as ContentMock;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    public function test_create_newletter_issue_works()
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
            'description' => "<p><img style=\"display: block; margin-left: auto; margin-right: auto;\" title=\"Tiny Logo\" src=\"https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png\" alt=\"TinyMCE Logo\" width=\"128\" height=\"128\" /></p> <h2 style=\"text-align: center;\">Welcome to the TinyMCE editor demo!</h2> <h2>Got questions or need help?</h2> <ul> <li>Our <a href=\"https://www.tiny.cloud/docs/\">documentation</a> is a great resource for learning how to configure TinyMCE.</li> <li>Have a specific question? Try the <a href=\"https://stackoverflow.com/questions/tagged/tinymce\" target=\"_blank\" rel=\"noopener\"><code>tinymce</code> tag at Stack Overflow</a>.</li> <li>We also offer enterprise grade support as part of <a href=\"https://www.tiny.cloud/pricing\">TinyMCE premium plans</a>.</li> </ul> <h2>A simple table to play with</h2> <table style=\"border-collapse: collapse; width: 100%;\" border=\"1\"> <thead> <tr> <th>Product</th> <th>Cost</th> <th>Really?</th> </tr> </thead> <tbody> <tr> <td>TinyMCE</td> <td>Free</td> <td>YES!</td> </tr> <tr> <td>Plupload</td> <td>Free</td> <td>YES!</td> </tr> </tbody> </table> <h2>Found a bug?</h2> <p> If you think you have found a bug please create an issue on the <a href=\"https://github.com/tinymce/tinymce/issues\">GitHub repo</a> to report it to the developers. </p> <h2>Finally ...</h2> <p> Don't forget to check out our other product <a href=\"http://www.plupload.com\" target=\"_blank\">Plupload</a>, your ultimate upload solution featuring HTML5 upload support. </p> <p> Thanks for supporting TinyMCE! We hope it helps you and your users create great content.<br>All the best from the TinyMCE team. </p>",
        ];
        $response = $this->json('POST', "/api/v1/contents/{$newsletter->id}/issues", $request);
        $response->assertStatus(200)->assertJsonStructure(ContentMock::STANDARD_ISSUE_RESPONSE);
        $this->assertDatabaseHas('content_issues', [
            'title' => $request['title'],
            'description' => $request['description'],
            'content_id' => $newsletter->id,
            'is_available' => 0,
            'views' => 0,
        ]);
    }

    public function test_update_newletter_issue_works()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();
        $newsletter = Content::factory()->state([
            'type' => 'newsletter',
            'user_id' => $user->id,
        ])
        ->hasAttached(
            $digiverse,
            [
                'id' => Str::uuid(),
            ]
        )
        ->create();

        $issue = ContentIssue::factory()
        ->state([
            'title' => 'First Issue ' . date('YmdHis'),
            'is_available' => 0,
            'description' => "<p><img style=\"display: block; margin-left: auto; margin-right: auto;\" title=\"Tiny Logo\" src=\"https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png\" alt=\"TinyMCE Logo\" width=\"128\" height=\"128\" /></p> <h2 style=\"text-align: center;\">Welcome to the TinyMCE editor demo!</h2> <h2>Got questions or need help?</h2> <ul> <li>Our <a href=\"https://www.tiny.cloud/docs/\">documentation</a> is a great resource for learning how to configure TinyMCE.</li> <li>Have a specific question? Try the <a href=\"https://stackoverflow.com/questions/tagged/tinymce\" target=\"_blank\" rel=\"noopener\"><code>tinymce</code> tag at Stack Overflow</a>.</li> <li>We also offer enterprise grade support as part of <a href=\"https://www.tiny.cloud/pricing\">TinyMCE premium plans</a>.</li> </ul> <h2>A simple table to play with</h2> <table style=\"border-collapse: collapse; width: 100%;\" border=\"1\"> <thead> <tr> <th>Product</th> <th>Cost</th> <th>Really?</th> </tr> </thead> <tbody> <tr> <td>TinyMCE</td> <td>Free</td> <td>YES!</td> </tr> <tr> <td>Plupload</td> <td>Free</td> <td>YES!</td> </tr> </tbody> </table> <h2>Found a bug?</h2> <p> If you think you have found a bug please create an issue on the <a href=\"https://github.com/tinymce/tinymce/issues\">GitHub repo</a> to report it to the developers. </p> <h2>Finally ...</h2> <p> Don't forget to check out our other product <a href=\"http://www.plupload.com\" target=\"_blank\">Plupload</a>, your ultimate upload solution featuring HTML5 upload support. </p> <p> Thanks for supporting TinyMCE! We hope it helps you and your users create great content.<br>All the best from the TinyMCE team. </p>"
        ])
        ->for($newsletter, 'content')
        ->create();

        $request = [
            'issue_id' => $issue->id,
            'title' => 'Update First Issue ' . date('YmdHis'),
            'description' => '<p>Hello World</p>',
        ];
        $response = $this->json('PUT', "/api/v1/contents/{$newsletter->id}/issues", $request);
        $response->assertStatus(200)->assertJsonStructure(ContentMock::STANDARD_ISSUE_RESPONSE);
        $this->assertDatabaseHas('content_issues', [
            'id' => $issue->id,
            'title' => $request['title'],
            'description' => $request['description'],
            'content_id' => $newsletter->id,
            'is_available' => 0,
            'views' => 0,
        ]);
    }

    public function test_publish_newletter_issue_works()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();
        $newsletter = Content::factory()->state([
            'type' => 'newsletter',
            'user_id' => $user->id,
        ])
        ->hasAttached(
            $digiverse,
            [
                'id' => Str::uuid(),
            ]
        )
        ->hasAttached(
            User::factory()->count(1),
            [
                'id' => Str::uuid(),
            ],
            'subscribers'
        )
        ->hasAttached(
            User::factory()->count(1),
            [
                'id' => Str::uuid(),
            ],
            'subscribers'
        )
        ->hasAttached(
            User::factory()->count(1),
            [
                'id' => Str::uuid(),
            ],
            'subscribers'
        )
        ->hasAttached(
            User::factory()->count(1),
            [
                'id' => Str::uuid(),
            ],
            'subscribers'
        )
        ->create();

        $issue = ContentIssue::factory()
        ->state([
            'title' => 'First Issue ' . date('YmdHis'),
            'description' => '<p>Hello World</p>',
            'is_available' => 0,
        ])
        ->for($newsletter, 'content')
        ->create();

        $this->assertTrue($newsletter->notifiers()->count() === 0);
        $this->assertDatabaseHas('content_issues', [
            'id' => $issue->id,
            'content_id' => $newsletter->id,
            'is_available' => 0,
        ]);
        $request = [
            'issue_id' => $issue->id,
        ];
        $response = $this->json('PATCH', "/api/v1/contents/{$newsletter->id}/issues", $request);
        $response->assertStatus(200)->assertJsonStructure(ContentMock::STANDARD_ISSUE_RESPONSE);
        $this->assertDatabaseHas('content_issues', [
            'id' => $issue->id,
            'content_id' => $newsletter->id,
            'is_available' => 1,
        ]);
        $this->assertTrue($newsletter->notifiers()->count() === 4);
    }
}
