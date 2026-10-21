<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scope' => ['sometimes', Rule::in(['all', 'mine', 'unassigned'])],
            'project_id' => ['sometimes', 'integer', Rule::exists('projects', 'id')],
            'assignee_id' => ['sometimes', 'integer', Rule::exists('users', 'id')],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'type' => ['sometimes', Rule::enum(TaskType::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'visibility' => ['sometimes', Rule::in(['active', 'archived', 'all'])],
            'q' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', Rule::in(['created_at', 'updated_at', 'title', 'difficulty', 'priority', 'due_date', 'position'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
