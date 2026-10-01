<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<table>
  <!-- 1. TITLE & HEADER -->
  <tr>
    <th colspan="16" style="font-size: 15pt; font-weight: bold; text-align: center; background-color: #1e1b4b; color: #ffffff; height: 32px;">
      LAPORAN RIWAYAT MUTASI SYIRKAH (BUKU KAS)
    </th>
  </tr>
  <tr>
    <td colspan="16" style="text-align: center; font-size: 9pt; color: #64748b; background-color: #f8fafc;">
      Dicetak pada: {{ now()->translatedFormat('d F Y H:i:s') }} | Oleh: {{ auth()->user()?->name ?? 'System' }}
    </td>
  </tr>
  <tr>
    <td colspan="16"></td>
  </tr>

  <!-- 2. FILTER INFO -->
  <tr>
    <th colspan="2" style="font-weight: bold; background-color: #f1f5f9; text-align: left;">Periode:</th>
    <td colspan="6" style="text-align: left;">{{ $periodLabel }}</td>
    <th colspan="2" style="font-weight: bold; background-color: #f1f5f9; text-align: left;">Divisi:</th>
    <td colspan="6" style="text-align: left;">{{ $divisionLabel }}</td>
  </tr>
  <tr>
    <th colspan="2" style="font-weight: bold; background-color: #f1f5f9; text-align: left;">Status Mutasi:</th>
    <td colspan="6" style="text-align: left;">{{ $statusLabel }}</td>
    <th colspan="2" style="font-weight: bold; background-color: #f1f5f9; text-align: left;">Jenis Transaksi:</th>
    <td colspan="6" style="text-align: left;">{{ $typeLabel }}</td>
  </tr>
  <tr>
    <td colspan="16"></td>
  </tr>

  <!-- 3. SUMMARY SECTION (CARD SUMMARY) -->
  <tr>
    <th colspan="4" style="background-color: #3730a3; color: #ffffff; font-weight: bold; text-align: center; font-size: 11pt;">
      TOTAL SALDO WAJIB
    </th>
    <th colspan="4" style="background-color: #065f46; color: #ffffff; font-weight: bold; text-align: center; font-size: 11pt;">
      TOTAL SALDO SUKARELA
    </th>
    <th colspan="4" style="background-color: #075985; color: #ffffff; font-weight: bold; text-align: center; font-size: 11pt;">
      TOTAL MUTASI (NET)
    </th>
    <th colspan="4" style="background-color: #6b21a8; color: #ffffff; font-weight: bold; text-align: center; font-size: 11pt;">
      ARUS KAS KESELURUHAN
    </th>
  </tr>
  <tr>
    <td colspan="4" style="text-align: center; font-size: 13pt; font-weight: bold; background-color: #e0e7ff; color: #1e1b4b; height: 26px;">
      Rp {{ number_format($totalWajib, 0, ',', '.') }}
    </td>
    <td colspan="4" style="text-align: center; font-size: 13pt; font-weight: bold; background-color: #d1fae5; color: #064e3b; height: 26px;">
      Rp {{ number_format($totalSukarela, 0, ',', '.') }}
    </td>
    <td colspan="4" style="text-align: center; font-size: 13pt; font-weight: bold; background-color: #e0f2fe; color: #082f49; height: 26px;">
      Rp {{ number_format($totalMutasi, 0, ',', '.') }}
    </td>
    <td colspan="4" style="text-align: center; font-size: 13pt; font-weight: bold; background-color: #f3e8ff; color: #581c87; height: 26px;">
      {{ $totalTransactionsCount }} Total Mutasi
    </td>
  </tr>
  <tr>
    <td colspan="4" style="background-color: #eef2ff; text-align: center; font-size: 9pt; color: #3730a3;">
      Masuk: +Rp {{ number_format($wajibCredit, 0, ',', '.') }} | Keluar: -Rp {{ number_format($wajibDebit, 0, ',', '.') }}
    </td>
    <td colspan="4" style="background-color: #ecfdf5; text-align: center; font-size: 9pt; color: #065f46;">
      Masuk: +Rp {{ number_format($sukarelaCredit, 0, ',', '.') }} | Keluar: -Rp {{ number_format($sukarelaDebit, 0, ',', '.') }}
    </td>
    <td colspan="4" style="background-color: #f0f9ff; text-align: center; font-size: 9pt; color: #075985;">
      Masuk: +Rp {{ number_format($totalCredit, 0, ',', '.') }} | Keluar: -Rp {{ number_format($totalDebit, 0, ',', '.') }}
    </td>
    <td colspan="4" style="background-color: #faf5ff; text-align: center; font-size: 9pt; color: #6b21a8;">
      Masuk: +Rp {{ number_format($totalCredit, 0, ',', '.') }} ({{ $creditCount }} Tx) | Keluar: -Rp {{ number_format($totalDebit, 0, ',', '.') }} ({{ $debitCount }} Tx)
    </td>
  </tr>
  <tr>
    <td colspan="16"></td>
  </tr>

  <!-- 4. DETAIL TABLE HEADINGS -->
  <thead>
    <tr>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: center;">No</th>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: center;">Tanggal &amp; Waktu</th>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: center;">NIP</th>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: left;">Nama Karyawan</th>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: left;">Divisi</th>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: left;">Program Syirkah</th>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: center;">Tipe Transaksi</th>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: right;">Mutasi Wajib (Rp)</th>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: right;">Mutasi Sukarela (Rp)</th>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: right;">Nominal Mutasi (Rp)</th>
      <th style="background-color: #312e81; color: #ffffff; font-weight: bold; text-align: right;">Total Saldo Wajib (Rp)</th>
      <th style="background-color: #064e3b; color: #ffffff; font-weight: bold; text-align: right;">Total Saldo Sukarela (Rp)</th>
      <th style="background-color: #0c4a6e; color: #ffffff; font-weight: bold; text-align: right;">Total Saldo (NET) (Rp)</th>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: center;">Status</th>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: left;">Disetujui Oleh</th>
      <th style="background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: left;">Keterangan / Ref</th>
    </tr>
  </thead>
  <tbody>
    @forelse($transactions as $index => $tx)
      @php
        $isDeposit = $tx->transaction_type === 'deposit';
        $nom = (float) ($tx->mandatory_amount + $tx->secondary_amount);
        $netBalance = (float) ($tx->balance_mandatory + $tx->balance_secondary);
        $statusUpper = strtoupper($tx->status ?? 'APPROVED');
        $statusColor = match($tx->status) {
          'approved' => '#059669',
          'pending' => '#d97706',
          'rejected' => '#e11d48',
          default => '#475569',
        };
      @endphp
      <tr>
        <td style="text-align: center;">{{ $index + 1 }}</td>
        <td style="text-align: center;">{{ $tx->created_at ? $tx->created_at->format('d/m/Y H:i') : '-' }}</td>
        <td style="text-align: center;">{{ $tx->user?->nip ?? '-' }}</td>
        <td>{{ $tx->user?->name ?? '-' }}{{ !$tx->user?->is_attendance_required ? ' [Non-Absen]' : '' }}</td>
        <td>{{ $tx->user?->division?->name ?? '-' }}</td>
        <td>{{ $tx->masterSaving?->savings_name ?? 'Program Syirkah' }}</td>
        <td style="text-align: center;">{{ $isDeposit ? 'Setoran (Deposit)' : 'Penarikan (Withdrawal)' }}</td>
        <td style="text-align: right; color: {{ $isDeposit ? '#059669' : '#e11d48' }};">
          {{ $isDeposit ? '+' : '-' }}Rp {{ number_format($tx->mandatory_amount, 0, ',', '.') }}
        </td>
        <td style="text-align: right; color: {{ $isDeposit ? '#059669' : '#e11d48' }};">
          {{ $isDeposit ? '+' : '-' }}Rp {{ number_format($tx->secondary_amount, 0, ',', '.') }}
        </td>
        <td style="text-align: right; font-weight: bold; color: {{ $isDeposit ? '#059669' : '#e11d48' }};">
          {{ $isDeposit ? '+' : '-' }}Rp {{ number_format($nom, 0, ',', '.') }}
        </td>
        <!-- Running balances for that employee -->
        <td style="text-align: right; background-color: #f5f3ff; font-weight: 500;">
          Rp {{ number_format($tx->balance_mandatory, 0, ',', '.') }}
        </td>
        <td style="text-align: right; background-color: #f0fdf4; font-weight: 500;">
          Rp {{ number_format($tx->balance_secondary, 0, ',', '.') }}
        </td>
        <td style="text-align: right; font-weight: bold; background-color: #f0f9ff; color: #0c4a6e;">
          Rp {{ number_format($netBalance, 0, ',', '.') }}
        </td>
        <td style="text-align: center; font-weight: bold; color: {{ $statusColor }};">
          {{ $statusUpper }}
        </td>
        <td>{{ $tx->approver?->name ?? '-' }}</td>
        <td>{{ $tx->description ?? '-' }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="16" style="text-align: center; font-style: italic; color: #64748b; padding: 10px;">
          Tidak ada data riwayat mutasi syirkah yang sesuai filter.
        </td>
      </tr>
    @endforelse
  </tbody>
</table>
</body>
</html>
