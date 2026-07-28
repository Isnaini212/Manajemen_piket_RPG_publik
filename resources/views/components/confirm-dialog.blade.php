<div
    x-data="confirmDialog()"
    x-on:confirm-dialog.window="open($event.detail)"
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/75 backdrop-blur-sm" @click="cancel()"></div>

    {{-- Dialog Box --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative bg-[#14102a] border-2 w-full max-w-sm"
        :class="type === 'danger'
            ? 'border-[#e63946] shadow-[0_0_30px_rgba(230,57,70,0.25)]'
            : 'border-[#f5c518] shadow-[0_0_30px_rgba(245,197,24,0.2)]'"
        @click.stop
    >
        {{-- Title bar --}}
        <div class="px-4 py-2.5 flex items-center justify-between"
             :class="type === 'danger' ? 'bg-[#e63946]' : 'bg-[#f5c518]'">
            <span class="font-pixel text-[9px] text-[#0c0918] tracking-wider" x-text="title">KONFIRMASI</span>
            <button @click="cancel()"
                    class="font-pixel text-[11px] text-[#0c0918] leading-none opacity-70 hover:opacity-100 transition-opacity">✕</button>
        </div>

        {{-- Icon + Message --}}
        <div class="p-5">
            <div class="flex items-start gap-4">
                <div class="shrink-0 w-10 h-10 flex items-center justify-center border-2 bg-[#0c0918]"
                     :class="type === 'danger'
                         ? 'border-[#e63946] text-[#e63946]'
                         : 'border-[#f5c518] text-[#f5c518]'">
                    <span class="text-base" x-text="type === 'danger' ? '☠' : '⚠'"></span>
                </div>
                <div class="flex-1 min-w-0 pt-1.5">
                    <p class="text-[13px] text-[#e8e8f0] leading-relaxed" x-text="message"></p>
                </div>
            </div>
        </div>

        {{-- Pixel divider --}}
        <div class="mx-5 border-t border-dashed border-[#2d2050]"></div>

        {{-- Actions --}}
        <div class="p-4 flex gap-3 justify-end">
            <button
                @click="cancel()"
                class="px-4 py-2 font-pixel text-[9px] border-2 border-[#2d2050] text-[#8888aa]
                       hover:border-[#8888aa] hover:text-[#e8e8f0] transition-all cursor-pointer"
            >BATAL</button>
            <button
                @click="confirm()"
                class="px-4 py-2 font-pixel text-[9px] transition-all hover:brightness-110 cursor-pointer"
                :class="type === 'danger'
                    ? 'bg-[#e63946] text-[#0c0918]'
                    : 'bg-[#f5c518] text-[#0c0918]'"
                x-text="confirmLabel"
            ></button>
        </div>

        {{-- Pixel corner cuts --}}
        <span class="absolute -top-[2px] -right-[2px] w-2 h-2 bg-[#0c0918]"></span>
        <span class="absolute -bottom-[2px] -left-[2px] w-2 h-2 bg-[#0c0918]"></span>
    </div>
</div>

<script>
// ─────────────────────────────────────────────────────────────────────────────
// Alpine component data
// ─────────────────────────────────────────────────────────────────────────────
function confirmDialog() {
    return {
        isOpen: false,
        message: '',
        title: 'KONFIRMASI',
        confirmLabel: 'YA, LANJUTKAN',
        type: 'warning',
        _onConfirm: null,
        _onCancel: null,

        open(detail) {
            this.message      = detail.message      ?? '';
            this.title        = detail.title        ?? 'KONFIRMASI';
            this.confirmLabel = detail.confirmLabel ?? 'YA, LANJUTKAN';
            this.type         = detail.type         ?? 'warning';
            this._onConfirm   = detail.onConfirm    ?? null;
            this._onCancel    = detail.onCancel     ?? null;
            this.isOpen       = true;
        },

        confirm() {
            this.isOpen = false;
            if (this._onConfirm) this._onConfirm();
        },

        cancel() {
            this.isOpen = false;
            if (this._onCancel) this._onCancel();
        },
    };
}

// ─────────────────────────────────────────────────────────────────────────────
// Replace Livewire's wire:confirm handler on a single element.
//
// Livewire 3 sets: el.__livewire_confirm = (action, instead) => {
//   if (confirm(message)) action(); else instead();
// }
// We replace that function with one that opens our custom themed dialog.
// ─────────────────────────────────────────────────────────────────────────────
function patchConfirmEl(el) {
    if (!el || !el.hasAttribute || !el.hasAttribute('wire:confirm')) return;
    if (el.__rpg_patched) return;
    el.__rpg_patched = true;

    const rawMsg   = el.getAttribute('wire:confirm') || 'Apakah kamu yakin?';
    const isDanger = /FINAL|HAPUS|RESET|DELETE|☠|penalti/i.test(rawMsg);

    // Wait a tick for Livewire to set __livewire_confirm, then replace it.
    requestAnimationFrame(() => {
        el.__livewire_confirm = function(action, instead) {
            window.dispatchEvent(new CustomEvent('confirm-dialog', {
                detail: {
                    message:      rawMsg,
                    title:        isDanger ? '⚠ TINDAKAN BERBAHAYA' : '❓ KONFIRMASI',
                    confirmLabel: isDanger ? 'YA, LANJUTKAN' : 'OK',
                    type:         isDanger ? 'danger' : 'warning',
                    onConfirm:    () => { if (action) action(); },
                    onCancel:     () => { if (instead) instead(); },
                },
            }));
        };
    });
}

function patchAllInRoot(root) {
    const els = (root && root.querySelectorAll)
        ? root.querySelectorAll('[wire\\:confirm]')
        : document.querySelectorAll('[wire\\:confirm]');
    els.forEach(patchConfirmEl);
}

// ─────────────────────────────────────────────────────────────────────────────
// Run patching:
// 1. On livewire:init (initial page load)
// 2. On livewire:navigated (wire:navigate page transitions)
// 3. Via MutationObserver for any Livewire morphing DOM updates
// ─────────────────────────────────────────────────────────────────────────────
function initPatcher() {
    // Patch all elements currently in the DOM
    patchAllInRoot(document);

    // Watch for new or updated elements via MutationObserver
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== 1) return; // Element nodes only
                if (node.hasAttribute && node.hasAttribute('wire:confirm')) {
                    patchConfirmEl(node);
                }
                if (node.querySelectorAll) {
                    node.querySelectorAll('[wire\\:confirm]').forEach(patchConfirmEl);
                }
            });

            // Also handle attribute changes (e.g. Livewire re-rendering the same element)
            if (mutation.type === 'attributes' &&
                mutation.attributeName === 'wire:confirm' &&
                mutation.target.nodeType === 1) {
                mutation.target.__rpg_patched = false; // allow re-patch
                patchConfirmEl(mutation.target);
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['wire:confirm'],
    });
}

document.addEventListener('livewire:init',      () => { setTimeout(initPatcher, 50); });
document.addEventListener('livewire:navigated', () => { setTimeout(patchAllInRoot, 50); });
</script>
