<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=0.1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Laporan Ganti Jam</title>
  <link rel="icon" href="{{ asset('favicon.ico') }}?v=1.0.1" type="image/x-icon">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=1.0.1" type="image/x-icon">
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
    }
    #table {
      border-collapse: collapse;
      width: 100%;
      font-size: 12px;
    }
    #table th, #table td {
      border: 1px solid #aaa;
      padding: 8px;
    }
    #table th {
      background-color: #f2f2f2;
    }
    #table tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    #table tr:hover {
      background-color: #f5f5f5;
    }
    .text-center {
      text-align: center;
    }
  </style>
</head>
<body>
  <h1>Data Laporan Ganti Jam</h1>

  <div style="display: table; width: 100%; margin-bottom: 20px">
    <div style="display: table-cell;">
      <table>
        @if ($division)
          <tr>
            <td>Divisi</td>
            <td>:</td>
            <td>{{ App\Models\Division::find($division)->name ?? '-' }}</td>
          </tr>
        @endif
        @if ($jobTitle)
          <tr>
            <td>Jabatan</td>
            <td>:</td>
            <td>{{ App\Models\JobTitle::find($jobTitle)->name ?? '-' }}</td>
          </tr>
        @endif
        @if ($status)
          <tr>
            <td>Status</td>
            <td>:</td>
            <td>{{ ucfirst($status) }}</td>
          </tr>
        @endif
      </table>
    </div>
    <div style="display: table-cell; text-align: right;">
      @if ($month)
        @php
            $selectedDate = \Carbon\Carbon::parse($month)->settings(['formatFunction' => 'translatedFormat']);
        @endphp
        Bulan: {{ $selectedDate->format('F Y') }}
      @elseif ($week)
        @php
            $start = \Carbon\Carbon::parse($week)->startOfWeek();
            $end = \Carbon\Carbon::parse($week)->endOfWeek();
        @endphp
        Tanggal: {{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}
      @elseif ($date)
        Tanggal: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
      @endif
    </div>
  </div>

  <table id="table">
    <thead>
      <tr>
        <th>No.</th>
        <th>Karyawan</th>
        <th>NIP</th>
        <th>Tgl Diganti</th>
        <th>Tgl Ganti</th>
        <th>Target Shift</th>
        <th>Waktu</th>
        <th>Durasi</th>
        <th>Alasan</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($approvals as $approval)
        <tr>
          <td class="text-center">{{ $loop->iteration }}</td>
          <td>{{ $approval->user->name ?? '-' }}</td>
          <td>{{ $approval->user->nip ?? '-' }}</td>
          <td>{{ \Carbon\Carbon::parse($approval->replaced_date)->format('d M Y') }}</td>
          <td>{{ \Carbon\Carbon::parse($approval->replacement_date)->format('d M Y') }}</td>
          <td>{{ $approval->shift ? $approval->shift->name : '-' }}</td>
          <td class="text-center">{{ \Carbon\Carbon::parse($approval->start_hour)->format('H:i') }} - {{ \Carbon\Carbon::parse($approval->end_hour)->format('H:i') }}</td>
          <td>{{ $approval->formatted_duration }}</td>
          <td>{{ $approval->reason }}</td>
          <td class="text-center">
            @if($approval->status == 'pending')
              Pending
            @elseif($approval->status == 'approved')
              Approved
            @else
              Rejected
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="10" class="text-center" style="padding: 20px;">
            Tidak ada data
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
