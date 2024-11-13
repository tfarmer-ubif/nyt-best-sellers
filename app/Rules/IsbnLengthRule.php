<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class IsbnLengthRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //can only be 10 or 13 digit numeric string
        if (!is_numeric($value) || !in_array(strlen($value), [10, 13])) {
            $fail("The $attribute must be a 10 or 13 digit numeric string.");
        }
    }
}
