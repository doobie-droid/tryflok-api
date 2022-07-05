<?php 

test('list continents works', function()
{
    $response = $this->json('GET', '/api/v1/continents');
        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'continents' => [
                    [
                        'id',
                        'name',
                        'iso_code',
                    ],
                ],
            ],
        ]);
});