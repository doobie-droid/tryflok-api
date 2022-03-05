<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SumCheck implements Rule
{
    public $fields;
    public $expected_sum;
    public $response;

    /**
     * Create a new rule instance.
     *
     * @param string[] $fields
     * 
     * @return void
     */
    public function __construct(array $fields, float $expected_sum)
    {
        $this->fields = $fields;
        $this->expected_sum = $expected_sum;
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
        $base = (float) $value;
        foreach ($this->fields as $field_name) {
            if ($field_name !== $attribute) {
                $base += (float) request()->input($field_name);
            }
        }

        if ((float) $base !== $this->expected_sum) {
            $fields = implode(', ', $this->fields);
            $this->response = "The sum of :attribute and the following fields [{$fields}] must be equal to {$this->expected_sum}";
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
