<?php

namespace App\Livewire\Admin;

use App\Models\Semester;
use App\Models\SystemConfig;
use App\Services\Contracts\SemesterServiceInterface;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Konfigurasi Sistem')]
class ConfigPanel extends Component
{
    /** @var array<string, mixed> */
    public array $configs = [];

    public bool $showResetConfirm = false;

    public string $resetConfirmText = '';

    public bool $isSaving = false;

    /** Boolean-typed config keys (stored as 'true'/'false' strings). */
    private const BOOLEAN_KEYS = ['replacement_duty_enabled', 'convict_status_visible', 'require_email_verification'];

    /** String config keys with their defaults. */
    private const STRING_DEFAULTS = [
        'registration_token' => '',
    ];

    /** Numeric config keys with their defaults. */
    private const NUMERIC_DEFAULTS = [
        'citizen_weekly_missions' => 1,
        'convict_weekly_missions' => 3,
        'swap_limit_per_month' => 2,
        'replacement_duty_days' => 3,
        'redemption_period_weeks' => 1,
        'lives_max' => 3,
        'lives_on_recovery' => 1,
        'xp_per_mission' => 100,
        'xp_per_replacement_mission' => 50,
        'xp_per_life_buy' => 150,
        'lives_penalty' => 1,
        'xp_penalty' => 0,
    ];

    public function mount(): void
    {
        foreach (self::NUMERIC_DEFAULTS as $key => $default) {
            $this->configs[$key] = (int) (SystemConfig::get($key) ?? $default);
        }

        foreach (self::BOOLEAN_KEYS as $key) {
            $this->configs[$key] = filter_var(SystemConfig::get($key), FILTER_VALIDATE_BOOLEAN);
        }

        foreach (self::STRING_DEFAULTS as $key => $default) {
            $this->configs[$key] = (string) (SystemConfig::get($key) ?? $default);
        }
    }

    #[Computed]
    public function activeSemester(): ?Semester
    {
        return Semester::where('is_active', true)->first();
    }

    public function save(): void
    {
        $this->isSaving = true;

        try {
            $this->validate([
                'configs.citizen_weekly_missions' => ['required', 'integer', 'min:1', 'max:7'],
                'configs.convict_weekly_missions' => ['required', 'integer', 'min:1', 'max:6'],
                'configs.swap_limit_per_month' => ['required', 'integer', 'min:0', 'max:10'],
                'configs.replacement_duty_days' => ['required', 'integer', 'min:1', 'max:7'],
                'configs.redemption_period_weeks' => ['required', 'integer', 'min:1', 'max:8'],
                'configs.lives_max' => ['required', 'integer', 'min:1', 'max:10'],
                'configs.lives_on_recovery' => ['required', 'integer', 'min:1'],
                'configs.xp_per_mission' => ['required', 'integer', 'min:1'],
                'configs.xp_per_replacement_mission' => ['required', 'integer', 'min:0'],
                'configs.xp_per_life_buy' => ['required', 'integer', 'min:1'],
                'configs.lives_penalty' => ['required', 'integer', 'min:0', 'max:5'],
                'configs.xp_penalty' => ['required', 'integer', 'min:0'],
                'configs.registration_token' => ['nullable', 'string', 'max:100'],
            ]);

            foreach ($this->configs as $key => $value) {
                if (in_array($key, self::BOOLEAN_KEYS, true)) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
                }

                SystemConfig::set($key, (string) $value);
            }

            $this->dispatch('notify', message: 'Konfigurasi berhasil disimpan!', type: 'success');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('notify', message: 'Data tidak valid. Periksa kembali isian form.', type: 'error');
            throw $e;
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Gagal menyimpan konfigurasi. Coba lagi.', type: 'error');
        } finally {
            $this->isSaving = false;
        }
    }

    public function confirmReset(): void
    {
        $this->showResetConfirm = true;
        $this->resetConfirmText = '';
    }

    public function executeReset(): void
    {
        if ($this->resetConfirmText !== 'RESET') {
            $this->dispatch('notify', message: 'Ketik RESET untuk konfirmasi.', type: 'error');

            return;
        }

        app(SemesterServiceInterface::class)->resetAll();

        $this->reset('showResetConfirm', 'resetConfirmText');
        unset($this->activeSemester);

        $this->dispatch('notify', message: 'Semester berhasil direset.', type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.config-panel');
    }
}
