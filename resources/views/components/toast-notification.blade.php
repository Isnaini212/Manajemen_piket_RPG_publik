{{--
    Toast Notification Component
    Mendengarkan event Livewire 'notify' dan menampilkan popup notifikasi.
    Penggunaan:
        $this->dispatch('notify', message: 'Pesan berhasil!', type: 'success');
        $this->dispatch('notify', message: 'Terjadi error.', type: 'error');
        $this->dispatch('notify', message: 'Informasi.', type: 'info');
--}}
<div
    x-data="toastManager()"
    x-on:notify.window="add($event.detail)"
    class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none"
    style="max-width: 320px; width: calc(100vw - 2rem);"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0 scale-100"
            x-transition:leave-end="opacity-0 translate-x-8 scale-95"
            class="pointer-events-auto relative overflow-hidden border-2"
            :class="{
                'bg-[#0a2e14] border-[#2dc653] shadow-[0_0_20px_rgba(45,198,83,0.3)]': toast.type === 'success',
                'bg-[#2e0a0a] border-[#e63946] shadow-[0_0_20px_rgba(230,57,70,0.3)]': toast.type === 'error',
                'bg-[#14102a] border-[#f5c518] shadow-[0_0_20px_rgba(245,197,24,0.3)]': toast.type === 'info' || !toast.type,
            }"
        >
            {{-- Title bar --}}
            <div
                class="px-3 py-1.5 flex items-center justify-between gap-3"
                :class="{
                    'bg-[#2dc653]': toast.type === 'success',
                    'bg-[#e63946]': toast.type === 'error',
                    'bg-[#f5c518]': toast.type === 'info' || !toast.type,
                }"
            >
                <span class="font-pixel text-[8px] text-[#0c0918] tracking-wider" x-text="toast.title"></span>
                <button
                    @click="dismiss(toast.id)"
                    class="font-pixel text-[10px] text-[#0c0918] opacity-70 hover:opacity-100 leading-none"
                >✕</button>
            </div>

            {{-- Body --}}
            <div class="px-3 py-2.5 flex items-start gap-2">
                <p class="text-sm leading-snug" x-text="toast.message"
                   :class="{
                       'text-[#2dc653]': toast.type === 'success',
                       'text-[#e63946]': toast.type === 'error',
                       'text-[#f5c518]': toast.type === 'info' || !toast.type,
                   }"
                ></p>
            </div>

            {{-- Progress bar --}}
            <div
                class="absolute bottom-0 left-0 h-[3px] transition-all ease-linear"
                :class="{
                    'bg-[#2dc653]': toast.type === 'success',
                    'bg-[#e63946]': toast.type === 'error',
                    'bg-[#f5c518]': toast.type === 'info' || !toast.type,
                }"
                :style="'width: ' + toast.progress + '%; transition-duration: ' + toast.duration + 'ms'"
            ></div>
        </div>
    </template>
</div>

<script>
    function toastManager() {
        return {
            toasts: [],
            counter: 0,

            add({ message, type = 'info' }) {
                const id = ++this.counter;
                const duration = 4000;

                const titles = { success: 'BERHASIL', error: 'GAGAL', info: 'INFO' };

                this.toasts.push({
                    id,
                    message,
                    type,
                    title: titles[type] ?? 'NOTIFIKASI',
                    visible: true,
                    progress: 100,
                    duration,
                });

                // Trigger progress bar animation on next tick
                this.$nextTick(() => {
                    const toast = this.toasts.find(t => t.id === id);
                    if (toast) toast.progress = 0;
                });

                // Auto-dismiss
                setTimeout(() => this.dismiss(id), duration);
            },

            dismiss(id) {
                const toast = this.toasts.find(t => t.id === id);
                if (toast) {
                    toast.visible = false;
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 300);
                }
            },
        };
    }
</script>
