<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class VerifySubmissionRequest extends FormRequest
{
    /**
     * Only admins may verify submissions.
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
            'decision' => ['required', 'in:approved,rejected,rejected_final'],
            'rejection_reason' => [
                'required_if:decision,rejected',
                'required_if:decision,rejected_final',
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
}
