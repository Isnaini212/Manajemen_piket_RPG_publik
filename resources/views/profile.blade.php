@php
    $layout = auth()->user()->role === \App\Enums\UserRole::Admin ? 'admin-layout' : 'app-layout';
@endphp
<x-dynamic-component :component="$layout" title="Pengaturan Akun">
    <div class="py-6">
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="p-6 bg-[#14102a] pixel-box rounded-none">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-6 bg-[#14102a] pixel-box rounded-none">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

        </div>
    </div>
</x-dynamic-component>
