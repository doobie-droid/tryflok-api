<?php

namespace App\Services\Payment\Providers\Paystack;

class AmountConverter
{
    /**
     * @param $number
     * @return float|string
     */
    public static function convert($number)
    {
        $number = preg_replace('/\,/i', '', $number);
        $number = preg_replace('/([^0-9\.\-])/i', '', $number);
        if (! is_numeric($number)) {
            return 0.00;
        }

        $isCents = (bool) preg_match('/^0.\d+$/', $number);
        return ($isCents ? '0' : null) . number_format($number * 100., 0, '.', '');
    }
}
