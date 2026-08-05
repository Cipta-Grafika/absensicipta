<table>
  <thead>
    <tr>
      <th>nip</th>
      <th>nama_karyawan</th>
      <th>tanggal</th>
      <th>status</th>
      <th>divisi</th>
      <th>catatan</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($schedules as $sched)
      <tr>
        <td>{{ $sched->user?->nip ?? '' }}</td>
        <td>{{ $sched->user?->name ?? '' }}</td>
        <td>{{ $sched->date?->format('Y-m-d') }}</td>
        <td>{{ $sched->is_working_day ? 'Hari Kerja' : 'Hari Libur' }}</td>
        <td>{{ $sched->user?->division?->name ?? '' }}</td>
        <td>{{ $sched->note ?? '' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
