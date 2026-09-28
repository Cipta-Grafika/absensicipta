<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Rekapitulasi Cuti Karyawan {{ $year }}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
      color: #333;
      margin: 0;
      padding: 15px;
    }
    .header {
      margin-bottom: 20px;
      border-bottom: 2px solid #0284c7;
      padding-bottom: 10px;
    }
    .header h1 {
      font-size: 16px;
      margin: 0 0 5px 0;
      color: #0369a1;
      text-transform: uppercase;
    }
    .header p {
      margin: 0;
      color: #666;
      font-size: 9px;
    }
    .summary-box {
      margin-bottom: 15px;
      display: table;
      width: 100%;
    }
    .summary-cell {
      display: table-cell;
      padding: 6px 12px;
      background: #f0f9ff;
      border: 1px solid #bae6fd;
      border-radius: 4px;
      font-size: 9px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      border: 1px solid #cbd5e1;
      padding: 6px 8px;
      text-align: left;
    }
    th {
      background-color: #f1f5f9;
      font-weight: bold;
      text-transform: uppercase;
      font-size: 8.5px;
      color: #475569;
    }
    tr:nth-child(even) {
      background-color: #f8fafc;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .font-bold { font-weight: bold; }
    .badge-safe { color: #059669; font-weight: bold; }
    .badge-crit { color: #d97706; font-weight: bold; }
    .badge-empty { color: #dc2626; font-weight: bold; }
    .footer {
      margin-top: 20px;
      font-size: 8px;
      color: #94a3b8;
      text-align: right;
    }
  </style>
</head>
<body>

  <div class="header">
    <h1>Laporan Rekapitulasi & Saldo Cuti Karyawan</h1>
    <p>Periode Tahun: <strong>{{ $year }}</strong> &bull; Filter Divisi: <strong>{{ $summary['division'] }}</strong> &bull; Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}</p>
  </div>

  <table class="summary-box">
    <tr>
      <td class="summary-cell">Total Karyawan: <strong>{{ $summary['total_employees'] }} Orang</strong></td>
      <td class="summary-cell">Total Alokasi Kuota: <strong>{{ $summary['total_quota'] }} Hari</strong></td>
      <td class="summary-cell">Total Cuti Digunakan: <strong>{{ $summary['total_used'] }} Hari</strong></td>
      <td class="summary-cell">Sisa Saldo Kuota: <strong>{{ $summary['total_remaining'] }} Hari</strong></td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th style="width: 30px;" class="text-center">No</th>
        <th>Nama Karyawan</th>
        <th style="width: 70px;">NIP</th>
        <th style="width: 90px;">Divisi</th>
        <th style="width: 90px;">Jabatan</th>
        <th class="text-center" style="width: 50px;">Kuota Awal</th>
        <th class="text-center" style="width: 50px;">Sisa Lalu</th>
        <th class="text-center" style="width: 55px;">Penyesuaian</th>
        <th class="text-center font-bold" style="width: 55px;">Total Kuota</th>
        <th class="text-center font-bold" style="width: 50px;">Terpakai</th>
        <th class="text-center font-bold" style="width: 55px;">Sisa Saldo</th>
        <th class="text-center" style="width: 60px;">Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($employees as $index => $emp)
        @php
          $bal = $emp->leaveBalanceForYear($year);
          $rem = $bal->remaining_quota;
        @endphp
        <tr>
          <td class="text-center">{{ $index + 1 }}</td>
          <td class="font-bold">{{ $emp->name }}</td>
          <td>{{ $emp->nip ?? '-' }}</td>
          <td>{{ $emp->division?->name ?? '-' }}</td>
          <td>{{ $emp->jobTitle?->name ?? '-' }}</td>
          <td class="text-center">{{ $bal->initial_quota }}</td>
          <td class="text-center">{{ $bal->carry_forward }}</td>
          <td class="text-center">{{ $bal->adjustment > 0 ? '+' . $bal->adjustment : $bal->adjustment }}</td>
          <td class="text-center font-bold" style="color: #4f46e5;">{{ $bal->total_quota }}</td>
          <td class="text-center font-bold" style="color: #b45309;">{{ $bal->used_quota }}</td>
          <td class="text-center font-bold {{ $rem > 3 ? 'badge-safe' : ($rem > 0 ? 'badge-crit' : 'badge-empty') }}">
            {{ $rem }} Hari
          </td>
          <td class="text-center">
            @if ($rem > 3)
              <span class="badge-safe">Aman</span>
            @elseif ($rem > 0)
              <span class="badge-crit">Kritis</span>
            @else
              <span class="badge-empty">Habis</span>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="12" class="text-center" style="padding: 20px;">Tidak ada data karyawan.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="footer">
    Dokumen ini dicetak otomatis oleh HR Information System - SUPERADMIN GROUP Leave Management.
  </div>

</body>
</html>
