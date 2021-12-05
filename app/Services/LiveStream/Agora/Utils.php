<?php
namespace App\Services\LiveStream\Agora;

class Utils {
    public static function packString($value)
    {
        return pack("v", strlen($value)) . $value;
    }
}
