<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreDutySlotRequest extends FormRequest
{
    /**
     * Only admins may create duty slots.
     */
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
            'duty_date' => ['required', 'date', 'after_or_equal:today'],
            'quota' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
