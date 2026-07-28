<?php

use App\Models\User;
use App\Models\SystemConfig;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $registration_token = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $rules = [
            'name'     => ['required', 'string', "regex:/^[a-zA-Z\s\'.]+$/", 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'max:255', 'unique:'.User::class],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ];

        // Only validate token if one is configured
        $requiredToken = SystemConfig::get('registration_token');
        if ($requiredToken) {
            $rules['registration_token'] = ['required', 'string'];
        }

        $validated = $this->validate($rules, [
            'name.regex'                  => 'Nama lengkap hanya boleh berisi huruf dan spasi (nama asli).',
            'username.required'           => 'Username wajib diisi.',
            'username.alpha_dash'         => 'Username hanya boleh berisi huruf, angka, garis bawah (_), atau tanda hubung (-).',
            'username.unique'             => 'Username ini sudah digunakan.',
            'registration_token.required' => 'Token pendaftaran wajib diisi.',
        ]);

        // Check token value manually (case-insensitive)
        if ($requiredToken && strtoupper($this->registration_token) !== strtoupper($requiredToken)) {
            $this->addError('registration_token', 'Token pendaftaran tidak valid.');
            return;
        }

        $validated['password'] = Hash::make($validated['password']);
        unset($validated['registration_token']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $default = $user->role === \App\Enums\UserRole::Admin
            ? '/admin/dashboard'
            : '/student/dashboard';

        $this->redirect($default, navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register">
        <!-- Name -->
        <div>
            <x-input-label for="name" value="Nama Lengkap (Nama Asli)" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autofocus autocomplete="name" placeholder="Contoh: Ahmad Mulia" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Username -->
        <div class="mt-4">
            <x-input-label for="username" value="Username" />
            <x-text-input wire:model="username" id="username" class="block mt-1 w-full" type="text" name="username" required autocomplete="username" placeholder="Contoh: ahmad_mulia99" />
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4" x-data="{ show: false }">
            <x-input-label for="password" :value="__('Password')" />

            <div class="relative mt-1">
                <x-text-input wire:model="password" id="password" class="block w-full pr-10"
                                x-bind:type="show ? 'text' : 'password'"
                                type="password"
                                name="password"
                                required autocomplete="new-password" />
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#8888aa] hover:text-[#f5c518] focus:outline-none">
                    <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4" x-data="{ show: false }">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <div class="relative mt-1">
                <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block w-full pr-10"
                                x-bind:type="show ? 'text' : 'password'"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#8888aa] hover:text-[#f5c518] focus:outline-none">
                    <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        @if(\App\Models\SystemConfig::get('registration_token'))
        <!-- Registration Token -->
        <div class="mt-4">
            <x-input-label for="registration_token" value="Token Pendaftaran" />
            <x-text-input wire:model="registration_token" id="registration_token"
                          class="block mt-1 w-full font-mono tracking-widest uppercase"
                          type="text" name="registration_token"
                          placeholder="Masukkan token dari Admin / Guru"
                          autocomplete="off" />
            <x-input-error :messages="$errors->get('registration_token')" class="mt-2" />
        </div>
        @endif

        <div class="flex items-center justify-end mt-4">
            <a class="font-pixel text-[9px] text-[#8888aa] hover:text-[#f5c518] focus:outline-none" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</div>
