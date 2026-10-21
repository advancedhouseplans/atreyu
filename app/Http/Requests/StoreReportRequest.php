<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:20000',
            'type' => 'required|in:bug,feature,other',
            'severity' => 'required|in:low,med,high,blocker',
            'metadata' => [
                'required',
                'string',
                'max:1048576',
                'json',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_array(json_decode((string) $value, true))) {
                        $fail('The metadata field must contain a JSON object.');
                    }
                },
            ],
            'snapshot' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|max:10240',
        ];
    }
}
