<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class YoutubeUrl implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {   
        if (preg_match("/https?:\/\/(w{3}\.)?youtube\.com.+?(\s|$)/", $value, $matches)) {
            return true;
        }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The provided Url is not a valid youtube url.';
    }
}
