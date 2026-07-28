@extends('layouts.admin')

@section('title', 'Log Tukar Jadwal')

@section('content')
@php
    $statusOptions = [
        ''         => 'Semua',
        'pending'  => 'Pending',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
    ];

    $badgeClass = [
        'pending'  => 'bg-[#7c3aed]/10 text-[#a78bfa] border-[#7c3aed]/40',
        'accepted' => 'bg-[#16a34a]/10 text-[#4ade80] border-[#16a34a]/40',
        'rejected' => 'bg-[#e63946]/10 text-[#f87171] border-[#e63946]/40',
    ];
@endphp

<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="font-pixel text-sm text-[#f5c518]">Log Tukar Piket</h2>
            <p class="mt-1 text-xs text-[#8888aa]">Riwayat semua permintaan tukar jadwal piket antar siswa.</p>
        </div>
        <span class="font-pixel text-[8px] px-3 py-1 bg-[#7c3aed]/20 text-[#a78bfa] border border-[#7c3aed]/40 rounded-none">
            Total: {{ $swaps->total() }}
        </span>
    </div>

    <div class="flex gap-2 flex-wrap">
        @foreach ($statusOptions as $value => $label)
            @php $active = ($filter ?? '') === $value; @endphp
            <a href="{{ route('admin.swap-logs.index', array_filter(['filter' => $value])) }}"
               class="font-pixel text-[8px] px-3 py-2 border transition-colors duration-150 rounded-none
                      {{ $active
                            ? 'bg-[#f5c518] text-[#0c0918] border-[#f5c518]'
                            : 'bg-[#14102a] text-[#8888aa] border-[#2d2050] hover:text-[#f5c518] hover:border-[#f5c518]' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($swaps->isEmpty())
        <div class="bg-[#14102a] pixel-box py-16 text-center">
            <p class="font-pixel text-[8px] text-[#8888aa]">Tidak ada data swap.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach ($swaps as $swap)
                @php
                    $badgeCls    = $badgeClass[$swap->status->value] ?? 'bg-[#2d2050] text-[#8888aa] border-[#2d2050]';
                    $idx         = $swaps->firstItem() + $loop->index;
                @endphp

                <div class="bg-[#14102a] pixel-box rounded-none p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-pixel text-[8px] text-[#5a5a7d]">#{{ $idx }}</span>
                        <span class="font-pixel text-[8px] px-2 py-1 rounded-none border {{ $badgeCls }}">
                            {{ strtoupper($swap->status->value) }}
                        </span>
                    </div>

                    <div>
                        <p class="text-[9px] font-semibold text-[#8888aa] uppercase tracking-wide mb-1">Pengirim</p>
                        <p class="text-sm font-semibold text-[#e8e8f0]">{{ $swap->fromStudent?->user?->name ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-[9px] font-semibold text-[#8888aa] uppercase tracking-wide mb-1">Penerima</p>
                        <p class="text-sm font-semibold text-[#e8e8f0]">{{ $swap->toStudent?->user?->name ?? '-' }}</p>
                    </div>

                    <button onclick="openDetailModal({{ $swap->id }})"
                            class="w-full pixel-btn font-pixel text-[8px] py-2 bg-[#3b82f6]/10 text-[#3b82f6] border border-[#3b82f6] rounded-none hover:bg-[#3b82f6] hover:text-[#0c0918]">
                        Detail
                    </button>
                </div>
            @endforeach
        </div>

        @if ($swaps->hasPages())
            <div class="flex justify-end">
                {{ $swaps->links() }}
            </div>
        @endif
    @endif

</div>

<div id="detailModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-[#0c0918] border border-[#2d2050] rounded-none pixel-box max-w-md w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div class="p-4 space-y-4" id="modalContent">
            <div class="flex items-center justify-between">
                <h3 class="font-pixel text-sm text-[#f5c518]">Detail Tukar Piket</h3>
                <button onclick="closeDetailModal()" class="text-[#8888aa] hover:text-[#e8e8f0]">X</button>
            </div>
        </div>
    </div>
</div>

<script>
    const swapsData = @json($swapsData);
    const badgeClass = @json($badgeClass);

    function openDetailModal(id) {
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('modalContent');
        const swap = swapsData[id];

        if (!swap) return;

        content.innerHTML = `
            <div class="flex items-center justify-between">
                <h3 class="font-pixel text-sm text-[#f5c518]">Detail Tukar Piket</h3>
                <button onclick="closeDetailModal()" class="text-[#8888aa] hover:text-[#e8e8f0]">X</button>
            </div>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-pixel text-[8px] text-[#5a5a7d]">#${swap.display_id}</span>
                    <span class="font-pixel text-[8px] px-2 py-1 rounded-none border ${badgeClass[swap.status]}">
                        ${swap.status.toUpperCase()}
                    </span>
                </div>

                <div class="p-3 border border-[#2d2050] bg-[#14102a]">
                    <p class="text-[9px] font-semibold text-[#8888aa] uppercase tracking-wide mb-1">Pengirim</p>
                    <p class="text-sm font-semibold text-[#e8e8f0]">${swap.from_student}</p>
                    <p class="text-xs text-[#5a5a7d]">${swap.from_email}</p>
                </div>

                <div class="p-3 border border-[#2d2050] bg-[#14102a]">
                    <p class="text-[9px] font-semibold text-[#8888aa] uppercase tracking-wide mb-1">Jadwal Pengirim</p>
                    <p class="text-sm text-[#e8e8f0]">${swap.from_date}</p>
                    ${swap.from_time ? `<p class="text-xs text-[#5a5a7d]">${swap.from_time}</p>` : ''}
                </div>

                <div class="text-center text-lg">↔</div>

                <div class="p-3 border border-[#2d2050] bg-[#14102a]">
                    <p class="text-[9px] font-semibold text-[#8888aa] uppercase tracking-wide mb-1">Penerima</p>
                    <p class="text-sm font-semibold text-[#e8e8f0]">${swap.to_student}</p>
                    <p class="text-xs text-[#5a5a7d]">${swap.to_email}</p>
                </div>

                <div class="p-3 border border-[#2d2050] bg-[#14102a]">
                    <p class="text-[9px] font-semibold text-[#8888aa] uppercase tracking-wide mb-1">Jadwal Penerima</p>
                    <p class="text-sm text-[#e8e8f0]">${swap.to_date}</p>
                    ${swap.to_time ? `<p class="text-xs text-[#5a5a7d]">${swap.to_time}</p>` : ''}
                </div>

                <div class="pt-3 border-t border-[#2d2050]">
                    <div class="flex items-center justify-between text-xs text-[#5a5a7d]">
                        <span>Dibuat: ${swap.created_at}</span>
                        <span>Respon: ${swap.responded_at}</span>
                    </div>
                </div>
            </div>
        `;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('detailModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDetailModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDetailModal();
        }
    });
</script>
@endsection
