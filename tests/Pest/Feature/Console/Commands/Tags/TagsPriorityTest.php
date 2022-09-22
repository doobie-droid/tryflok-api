<?php 

use App\Models;

test('insert tags with priority works', function()
{

    $tag1 = 'social'; //not in the list
    $tag2 = 'basketball'; //in the list

    $this->artisan('flok:tag-priority')->assertSuccessful();

    $tag_1 = Models\Tag::where('name', $tag1)->first();
    $this->assertEquals($tag_1->tag_priority, 0);

    $tag_2 = Models\Tag::where('name', $tag2)->first();
    $this->assertEquals($tag_2->tag_priority, 1);
})->skip();