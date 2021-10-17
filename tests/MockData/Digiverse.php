<?php

namespace Tests\MockData;

class Digiverse {
    const UNSEEDED_DIGIVERSE = [
        "title" => "The first Digiverse",
        "description" => "Testing digiverse creation",
        "price" => [
            "amount" => 100,
            "interval" => "monthly",
            "interval_amount" => 1,
        ],
        "tags" => [
            "0e14760d-1d41-45aa-a820-87d6dc35f7ff", 
            "120566de-0361-4d66-b458-321d4ede62a9"
        ],
        "cover" => [
            "asset_id" => "27c65768-c6c1-4875-872d-616c88062a7a",
        ],
    ];
}