<?php

namespace App\Http\Requests;

use App\Rules\IsbnLengthRule;
use Illuminate\Foundation\Http\FormRequest;

class BestSellersRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'author' => 'string',
            'isbn' => 'array', // array of 10 or 13 digit strings
            'isbn.*' => ['string', new IsbnLengthRule],
            'title' => 'string',
            'offset' => ['integer', 'gte:0', 'multiple_of:20'] // offset must be a multiple of 20
        ];
    }
}
