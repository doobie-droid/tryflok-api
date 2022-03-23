<?php

namespace App\Rules;

use App\Models\Asset;
use Illuminate\Contracts\Validation\Rule;

class AssetType implements Rule
{
    public $type;
    public $response;
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

        if ($this->type === 'newsletter') {
            if ($asset->asset_type === 'text') {
                return true;
            } else {
                $this->response = "The :attribute must be an asset of type text but type {$asset->asset_type} supplied";
                return false;
            }
        }

        if ($asset->asset_type !== $this->type) {
            $this->response = "The :attribute must be for an asset of type {$this->type} but type {$asset->asset_type} supplied";
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
