<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Bulanan Piket</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            text-transform: uppercase;
            margin: 0 0 5px 0;
        }
        .header p {
            font-size: 16px;
            margin: 0;
        }
        hr {
            border: 0;
            border-top: 2px solid #000;
            margin: 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 13px;
        }
        td {
            font-size: 13px;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-success {
            color: #15803d;
            font-weight: bold;
        }
        .text-danger {
            color: #b91c1c;
            font-weight: bold;
        }
        .student-name {
            font-weight: bold;
            font-size: 15px;
            display: block;
            margin-bottom: 2px;
        }
        .student-email {
            font-size: 11px;
            color: #444;
            display: block;
        }
        .empty-row {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Rekapitulasi Piket (Server Generated)</h1>
        <p>Bulan: {{ $monthName }} {{ $year }}</p>
        <hr>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 35%;">Nama Siswa</th>
                <th style="width: 15%;" class="text-center">Total Jadwal</th>
                <th style="width: 15%;" class="text-center">Selesai</th>
                <th style="width: 15%;" class="text-center">Gagal/Bolos</th>
                <th style="width: 15%;" class="text-center">Menunggu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $i => $student)
                <tr>
                    <td class="text-center font-bold">{{ $i + 1 }}</td>
                    <td>
                        <span class="student-name">{{ $student['name'] }}</span>
                        <span class="student-email">{{ $student['email'] }}</span>
                    </td>
                    <td class="text-center font-bold">{{ $student['total'] }}</td>
                    <td class="text-center text-success">{{ $student['approved'] }}</td>
                    <td class="text-center text-danger">{{ $student['failed'] }}</td>
                    <td class="text-center font-bold" style="color: #666;">{{ $student['others'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-row">Belum ada data siswa atau jadwal piket di sistem.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
