<?php

namespace App\Imports;

use App\Enums\DutySlotStatus;
use App\Models\DutySlot;
use App\Models\Semester;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DutySlotImport implements ToModel, WithHeadingRow, WithValidation
{
    private ?int $semesterId;
    public array $clashes = [];

    public function __construct()
    {
        $this->semesterId = Semester::where('is_active', true)->value('id');
    }

    /**
     * Map each row to a DutySlot model.
     * Heading row columns: tanggal | jam_mulai | jam_selesai | kuota
     */
    public function model(array $row): ?DutySlot
    {
        if (! $this->semesterId) {
            return null;
        }

        try {
            $formattedDate = \Illuminate\Support\Carbon::createFromFormat('d-m-Y', trim($row['tanggal']))->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }

        // Hapus data yang sudah di-softdelete terlebih dahulu untuk mencegah unique constraint violation
        DutySlot::onlyTrashed()
            ->where('semester_id', $this->semesterId)
            ->whereDate('duty_date', $formattedDate)
            ->forceDelete();

        // Skip baris yang sudah ada slot pada tanggal tersebut di semester aktif
        $exists = DutySlot::where('semester_id', $this->semesterId)
            ->whereDate('duty_date', $formattedDate)
            ->exists();

        if ($exists) {
            $this->clashes[] = $row['tanggal'];
            return null;
        }

        return new DutySlot([
            'semester_id' => $this->semesterId,
            'duty_date'   => $formattedDate,
            'quota'       => (int) $row['kuota'],
            'status'      => DutySlotStatus::Open,
        ]);
    }

    /**
     * Validation rules for each row.
     */
    public function rules(): array
    {
        return [
            'tanggal'    => ['required', 'date_format:d-m-Y'],
            'jam_mulai'  => ['required'],
            'jam_selesai'=> ['required'],
            'kuota'      => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function customValidationMessages(): array
    {
        return [
            'tanggal.required'      => 'Kolom tanggal wajib diisi.',
            'tanggal.date_format'   => 'Format tanggal harus DD-MM-YYYY (contoh: 11-08-2026).',
            'jam_mulai.required'    => 'Kolom jam_mulai wajib diisi.',
            'jam_selesai.required'  => 'Kolom jam_selesai wajib diisi.',
            'kuota.required'        => 'Kolom kuota wajib diisi.',
            'kuota.integer'         => 'Kuota harus berupa angka.',
            'kuota.min'             => 'Kuota minimal 1.',
        ];
    }
}
