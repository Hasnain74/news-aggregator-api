<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ArticleIndexRequest
 * @package App\Http\Requests
 */
class ArticleIndexRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'source' => ['required'],
            'category' => ['required'],
            'author' => ['required'],
            'per_page' => ['integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'The items per page must be an integer.',
            'per_page.min' => 'The items per page must be at least 1.',
            'per_page.max' => 'The items per page may not be greater than 100.',
        ];
    }
}
