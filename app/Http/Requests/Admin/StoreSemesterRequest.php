<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreSemesterRequest extends FormRequest
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->input('start_date');
            $end = $this->input('end_date');

            if ($start && $end) {
                // Rentang waktu bertabrakan jika:
                // Start_date_baru <= End_date_lama AND End_date_baru >= Start_date_lama
                $overlap = \App\Models\Semester::where('start_date', '<=', $end)
                    ->where('end_date', '>=', $start)
                    ->exists();

                if ($overlap) {
                    $validator->errors()->add(
                        'start_date',
                        'Gagal! Rentang tanggal ini bertabrakan dengan semester yang sudah ada.'
                    );
                }
            }
        });
    }
}
