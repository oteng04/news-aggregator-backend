<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleSearchRequest extends FormRequest
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
            'q' => ['required', 'string', 'min:1', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'source_id' => ['nullable', 'integer', 'exists:sources,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_by' => ['nullable', 'string', Rule::in(['published_at', 'created_at', 'title', 'relevance'])],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'date_from' => ['nullable', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Search query is required.',
            'q.string' => 'Search query must be a string.',
            'q.min' => 'Search query must be at least 1 character.',
            'q.max' => 'Search query cannot exceed 255 characters.',
            'page.integer' => 'Page must be a valid number.',
            'page.min' => 'Page must be at least 1.',
            'page.max' => 'Page cannot exceed 100.',
            'per_page.integer' => 'Per page must be a valid number.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 50 for search results.',
            'source_id.exists' => 'Selected source does not exist.',
            'category_id.exists' => 'Selected category does not exist.',
            'sort_by.in' => 'Sort by must be one of: published_at, created_at, title, relevance.',
            'sort_order.in' => 'Sort order must be either asc or desc.',
            'date_from.date' => 'Date from must be a valid date.',
            'date_from.before_or_equal' => 'Date from cannot be in the future.',
            'date_to.date' => 'Date to must be a valid date.',
            'date_to.after_or_equal' => 'Date to must be after or equal to date from.',
            'date_to.before_or_equal' => 'Date to cannot be in the future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'q' => 'search query',
            'per_page' => 'results per page',
            'source_id' => 'source filter',
            'category_id' => 'category filter',
            'sort_by' => 'sort field',
            'sort_order' => 'sort order',
            'date_from' => 'start date',
            'date_to' => 'end date',
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
            'sort_by' => $this->get('sort_by', 'relevance'),
            'sort_order' => $this->get('sort_order', 'desc'),
        ]);

        // Handle date parameters
        if ($this->has('date_from') && !empty($this->get('date_from'))) {
            $this->merge([
                'date_from' => $this->get('date_from'),
            ]);
        }

        if ($this->has('date_to') && !empty($this->get('date_to'))) {
            $this->merge([
                'date_to' => $this->get('date_to'),
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $dateFrom = $this->get('date_from');
            $dateTo = $this->get('date_to');

            if ($dateFrom && $dateTo) {
                $fromDate = strtotime($dateFrom);
                $toDate = strtotime($dateTo);

                if ($fromDate > $toDate) {
                    $validator->errors()->add('date_to', 'End date must be after start date.');
                }

                // Check if date range is too broad (more than 1 year)
                $daysDiff = ($toDate - $fromDate) / (60 * 60 * 24);
                if ($daysDiff > 365) {
                    $validator->errors()->add('date_range', 'Date range cannot exceed 1 year.');
                }
            }
        });
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

    /**
     * Get search-specific parameters
     */
    public function getSearchParameters(): array
    {
        return [
            'query' => $this->validated('q'),
            'filters' => [
                'source_id' => $this->validated('source_id'),
                'category_id' => $this->validated('category_id'),
                'date_from' => $this->validated('date_from'),
                'date_to' => $this->validated('date_to'),
            ],
            'pagination' => [
                'page' => $this->validated('page'),
                'per_page' => $this->validated('per_page'),
            ],
            'sorting' => [
                'sort_by' => $this->validated('sort_by'),
                'sort_order' => $this->validated('sort_order'),
            ],
        ];
    }
}
