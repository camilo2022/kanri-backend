<?php

namespace App\Http\Requests\Cache\Builder;

use App\Services\PermissionService;
use Illuminate\Foundation\Http\FormRequest;

class BuilderAllRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['cache.builders.all'];
    }

    public function rules(): array
    {
        return [];
    }
}
