<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public API endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'source_id' => ['nullable', 'integer', 'exists:sources,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'search' => ['nullable', 'string', 'min:1', 'max:255'],
            'sort_by' => ['nullable', 'string', Rule::in(['published_at', 'created_at', 'title'])],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'Page must be a valid number.',
            'page.min' => 'Page must be at least 1.',
            'page.max' => 'Page cannot exceed 1000.',
            'per_page.integer' => 'Per page must be a valid number.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 100.',
            'source_id.exists' => 'Selected source does not exist.',
            'category_id.exists' => 'Selected category does not exist.',
            'search.min' => 'Search query must be at least 1 character.',
            'search.max' => 'Search query cannot exceed 255 characters.',
            'sort_by.in' => 'Sort by must be one of: published_at, created_at, title.',
            'sort_order.in' => 'Sort order must be either asc or desc.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'per_page' => 'per page',
            'source_id' => 'source',
            'category_id' => 'category',
            'sort_by' => 'sort by',
            'sort_order' => 'sort order',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'page' => $this->get('page', 1),
            'per_page' => $this->get('per_page', 20),
            'sort_by' => $this->get('sort_by', 'published_at'),
            'sort_order' => $this->get('sort_order', 'desc'),
        ]);
    }

    /**
     * Get the validated data from the request.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Convert string numbers to integers where appropriate
        if (isset($validated['page'])) {
            $validated['page'] = (int) $validated['page'];
        }
        if (isset($validated['per_page'])) {
            $validated['per_page'] = (int) $validated['per_page'];
        }
        if (isset($validated['source_id'])) {
            $validated['source_id'] = (int) $validated['source_id'];
        }
        if (isset($validated['category_id'])) {
            $validated['category_id'] = (int) $validated['category_id'];
        }

        return $validated;
    }
}
