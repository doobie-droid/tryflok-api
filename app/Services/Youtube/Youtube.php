<?php

namespace App\Services\Youtube;

use App\Services\Youtube\Main;
use App\Services\Youtube\Test;

class Youtube
{
    protected $driver;

    public function __construct()
    {
        if (config('app.env') == 'testing') {
            $this->driver = new Test;
        } else {
            $this->driver = new Main;
        }
    }

     public function fetchVideo(string $videoId): \stdClass
    {
        return $this->driver->fetchVideo($videoId);
    }
}
