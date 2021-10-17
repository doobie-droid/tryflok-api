<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Asset;

class AssetType implements Rule
{
    public $type, $response;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($type)
    {
        $this->type = $type;
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
        $asset = Asset::where('id', $value)->first();
        if (is_null($asset)) {
            return false;
        }

        if ($asset->asset_type !== $this->type) {
            $this->response = "The :attribute must be an asset of type {$this->type} but type {$asset->asset_type} supplied";
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->response;
    }
}
