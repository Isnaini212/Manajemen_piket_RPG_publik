<div class="space-y-6">
    {{-- Header (Hide in Print) --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
        <div>
            <h2 class="font-pixel text-lg text-[#f5c518]">Rekap Bulanan ({{ $this->months[$month] }} {{ $year }})</h2>
            <p class="mt-1 text-sm text-[#8888aa]">Rekapitulasi jadwal dan kehadiran piket siswa per bulan.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="exportDomPDF" wire:loading.attr="disabled" class="pixel-btn bg-[#7c3aed] text-[#e8e8f0] font-pixel text-[10px] px-4 py-2 hover:bg-[#6d28d9] transition-colors flex items-center gap-2">
                <span wire:loading.remove wire:target="exportDomPDF">🖨 CETAK PDF (DOMPDF)</span>
                <span wire:loading wire:target="exportDomPDF">⏳ PROSES...</span>
            </button>
            <button onclick="window.print()" class="pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[10px] px-4 py-2 hover:bg-[#e8e8f0] transition-colors">
                🖨 CETAK BROWSER (PRINT)
            </button>
        </div>
    </div>

    {{-- Filters (Hide in Print) --}}
    <div class="bg-[#14102a] pixel-box p-4 flex flex-wrap gap-4 items-end print:hidden">
        <div class="space-y-1">
            <label class="font-pixel text-[10px] text-[#8888aa]">Bulan</label>
            <select wire:model.live="month" class="w-40 bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] rounded-none px-3 py-2 text-sm focus:border-[#7c3aed] focus:ring-0">
                @foreach ($this->months as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="space-y-1">
            <label class="font-pixel text-[10px] text-[#8888aa]">Tahun</label>
            <select wire:model.live="year" class="w-32 bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] rounded-none px-3 py-2 text-sm focus:border-[#7c3aed] focus:ring-0">
                @foreach ($this->years as $yr)
                    <option value="{{ $yr }}">{{ $yr }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Print Header (Only visible in Print via CSS) --}}
    <div class="print-header mb-6 text-center text-black">
        <h1 class="text-2xl font-bold uppercase" style="font-family: Arial, sans-serif !important;">Laporan Rekapitulasi Piket</h1>
        <p class="text-lg mt-1" style="font-family: Arial, sans-serif !important;">Bulan: {{ $this->months[$month] }} {{ $year }}</p>
        <hr class="my-4 border-black border-t-2">
    </div>

    {{-- Data Table --}}
    <div class="bg-[#14102a] print:bg-white pixel-box print:border-none p-0 overflow-x-auto">
        <table class="w-full text-left border-collapse print:text-black">
            <thead>
                <tr class="border-b-2 border-[#2d2050] print:border-black bg-[#0c0918] print:bg-gray-200">
                    <th class="py-3 px-4 print-th">No</th>
                    <th class="py-3 px-4 print-th">Nama Siswa</th>
                    <th class="py-3 px-4 print-th text-center">Total Jadwal</th>
                    <th class="py-3 px-4 print-th text-center">Selesai</th>
                    <th class="py-3 px-4 print-th text-center">Gagal/Bolos</th>
                    <th class="py-3 px-4 print-th text-center">Menunggu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2d2050] print:divide-black">
                @forelse ($students as $i => $student)
                    <tr class="hover:bg-[#2d2050]/20 print:hover:bg-transparent">
                        <td class="py-3 px-4 text-sm print-td">{{ $i + 1 }}</td>
                        <td class="py-3 px-4 print-td">
                            <p class="text-sm font-semibold text-[#e8e8f0] print-name">{{ $student['name'] }}</p>
                            <p class="text-xs text-[#8888aa] print-email">{{ $student['email'] }}</p>
                        </td>
                        <td class="py-3 px-4 text-center font-pixel text-[10px] print-td">{{ $student['total'] }}</td>
                        <td class="py-3 px-4 text-center text-[#2dc653] font-pixel text-[10px] print-success">{{ $student['approved'] }}</td>
                        <td class="py-3 px-4 text-center text-[#e63946] font-pixel text-[10px] print-danger">{{ $student['failed'] }}</td>
                        <td class="py-3 px-4 text-center text-[#8888aa] font-pixel text-[10px] print-td">{{ $student['others'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-[#8888aa] print-td">
                            Belum ada data siswa atau jadwal piket di sistem.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <style>
    /* Sembunyikan elemen cetak di layar biasa */
    .print-header {
        display: none !important;
    }

    @media print {
        /* Tampilkan elemen cetak saat diprint */
        .print-header {
            display: block !important;
        }

        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        /* Override ALL fonts to Arial for readability and fix gray colors */
        * {
            font-family: Arial, Helvetica, sans-serif !important;
        }

        /* Hide specific layout elements that shouldn't be printed */
        aside, header, nav, .print\:hidden {
            display: none !important;
        }
        
        body {
            background-color: white !important;
            color: black !important;
            margin: 0;
            padding: 0;
        }

        .lg\:ml-60 { margin-left: 0 !important; }
        main { padding: 0 !important; }

        /* Fix cutoff table */
        .overflow-x-auto {
            overflow: visible !important;
        }
        table {
            width: 100% !important;
            table-layout: auto !important;
            border-collapse: collapse !important;
        }

        /* Explicit colors and bold text for print elements */
        .print-th {
            color: black !important;
            font-weight: bold !important;
            font-size: 14px !important;
            background-color: #f3f4f6 !important;
            border: 1px solid black !important;
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
        }
        
        .print-td {
            color: black !important;
            font-weight: bold !important;
            font-size: 14px !important;
            border: 1px solid black !important;
        }

        .print-name {
            color: black !important;
            font-weight: 900 !important; /* Extra tebal */
            font-size: 16px !important;
        }

        .print-email {
            color: #333333 !important;
            font-size: 12px !important;
        }

        .print-success {
            color: #15803d !important; /* Dark Green */
            font-weight: bold !important;
            font-size: 14px !important;
            border: 1px solid black !important;
        }

        .print-danger {
            color: #b91c1c !important; /* Dark Red */
            font-weight: bold !important;
            font-size: 14px !important;
            border: 1px solid black !important;
        }

        .pixel-box {
            border: none !important;
            box-shadow: none !important;
        }
    }
    </style>
</div>
