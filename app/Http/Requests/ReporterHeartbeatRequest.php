<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReporterHeartbeatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'url:http,https', 'max:2048'],
            'app_name' => ['required', 'string', 'max:255'],
            'environment' => ['required', 'string', 'max:32'],
            'package_version' => ['required', 'string', 'max:50'],
            'app_version' => ['nullable', 'string', 'max:255'],
            'laravel_version' => ['required', 'string', 'max:50'],
            'php_version' => ['required', 'string', 'max:50'],
        ];
    }
}
