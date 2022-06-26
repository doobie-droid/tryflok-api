<?php 

test('list language works', function()
{
    $response = $this->json('GET', '/api/v1/languages');
        $response->assertStatus(200)->assertJsonStructure([
            'status_code',
            'message',
            'data' => [
                'languages' => [
                    [
                        'id',
                        'name',
                        'iso_code',
                    ],
                ],
            ],
        ]);
});