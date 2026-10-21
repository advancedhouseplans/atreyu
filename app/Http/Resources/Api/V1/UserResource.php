<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\Appearance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `theme` ('light' or 'dark') is kept for the macOS client.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'appearance' => $this->appearance->value,
            'accent' => $this->accent->value,
            'theme' => $this->appearance === Appearance::Dark ? 'dark' : 'light',
        ];
    }
}
