<?php

test('list countries works', function(){
    $response = $this->json('GET', '/api/v1/countries');
    $response->assertStatus(200)->assertJsonStructure([
        'status',
        'message',
        'data' => [
            'countries' => [
                [
                    'id',
                    'name',
                    'iso_code',
                ],
            ],
        ],
    ]);
});