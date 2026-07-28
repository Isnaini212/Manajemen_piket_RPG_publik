<?php

namespace App\Http\Requests\Student;

use App\Enums\UserRole;
use App\Models\DutyClaim;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreSwapRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Siswa;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'from_claim_id' => ['required', 'exists:duty_claims,id'],
            // to_student_id is a target USER id here; the controller resolves it
            // to the student's profile id before storing. The "not myself" check
            // lives in withValidator() since it depends on the auth user.
            'to_student_id' => ['required', 'exists:users,id'],
        ];
    }

    /**
     * Extra checks: the source claim must belong to the current student, and
     * the target may not be the student themselves.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            $profileId = $user?->studentProfile?->id;

            $claim = DutyClaim::find($this->input('from_claim_id'));
            if ($claim && $claim->student_id !== $profileId) {
                $validator->errors()->add('from_claim_id', 'Klaim ini bukan milik kamu.');
            }

            if ((int) $this->input('to_student_id') === (int) $user?->id) {
                $validator->errors()->add('to_student_id', 'Tidak bisa menukar jadwal dengan diri sendiri.');
            }
        });
    }
}
