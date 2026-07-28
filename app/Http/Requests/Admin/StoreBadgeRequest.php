<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'icon' => ['nullable', 'image', 'max:2048'],
            'rule_groups' => ['required', 'array', 'min:1'],
            'rule_groups.*.logic_operator' => ['required', 'in:and,or'],
            'rule_groups.*.conditions' => ['required', 'array', 'min:1'],
            'rule_groups.*.conditions.*.field' => ['required', 'string'],
            'rule_groups.*.conditions.*.operator' => ['required', 'in:>=,<=,>,<,=,!='],
            'rule_groups.*.conditions.*.value' => ['required'],
        ];
    }
}
