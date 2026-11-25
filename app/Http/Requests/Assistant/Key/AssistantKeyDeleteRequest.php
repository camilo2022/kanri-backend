<?php

namespace App\Http\Requests\Assistant\Key;

use App\Services\PermissionService;
use Illuminate\Foundation\Http\FormRequest;

class AssistantKeyDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['assistant.connect'];
    }

    public function rules(): array
    {
        return [];
    }
}
