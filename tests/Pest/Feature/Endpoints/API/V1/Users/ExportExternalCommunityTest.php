<?php

use App\Models;
use Maatwebsite\Excel\Facades\Excel;

test('export works', function()
{
    Excel::fake();

    $user = Models\User::factory()->create();
    $externalCommunities = Models\ExternalCommunity::factory()
    ->for($user, 'owner')
    ->count(10)
    ->create();

    $this->actingAs($user)
         ->get('/api/v1/external-community');

    Excel::assertDownloaded('external-community.csv', function(App\Exports\ExternalCommunitiesExport $export) {
        // Assert that the correct export is downloaded.
        return $export->collection()->contains('#2018-01');
    });
});