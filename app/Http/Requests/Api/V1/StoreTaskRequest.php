<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TaskDifficulty;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')],
            'parent_id' => ['nullable', 'integer', Rule::exists('tasks', 'id')->whereNull('parent_id')],
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'type' => ['required', Rule::enum(TaskType::class)],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'difficulty' => ['nullable', 'integer', Rule::enum(TaskDifficulty::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_date' => ['nullable', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
