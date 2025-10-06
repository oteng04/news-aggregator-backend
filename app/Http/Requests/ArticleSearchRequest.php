<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'required|string|min:2|max:255',
            'source_id' => 'nullable|integer|exists:sources,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'Search query is required',
            'q.min' => 'Search query must be at least 2 characters',
            'q.max' => 'Search query cannot exceed 255 characters',
            'source_id.exists' => 'Selected source does not exist',
            'category_id.exists' => 'Selected category does not exist',
            'per_page.max' => 'Cannot request more than 100 articles per page',
        ];
    }
}