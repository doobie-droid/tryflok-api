<?php

test('list categories works', function(){
    $response = $this->json('GET', '/api/v1/categories');
        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'categories' => [
                    [
                        'id',
                        'name',
                    ],
                ],
            ],
        ]);
});