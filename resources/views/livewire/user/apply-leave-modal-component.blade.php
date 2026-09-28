<div>
    <x-dialog-modal wire:model.live="isModalOpen" maxWidth="2xl">
        <x-slot name="title">
            @if($modalMode === 'imp')
                Pengajuan IMP (Izin Meninggalkan Pekerjaan)
            @elseif($modalMode === 'sick')
                Pengajuan Sakit
            @elseif($modalMode === 'cuti')
                Pengajuan Cuti
            @else
                Pengajuan Izin
            @endif
        </x-slot>

        <x-slot name="content">
            <form wire:submit.prevent="submit" id="applyLeaveForm">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if($modalMode === 'leave')
                        <div class="sm:col-span-2">
                            <x-label for="status" value="{{ __('Jenis Izin') }}" />
                            <x-select id="status" class="mt-1 block w-full" wire:model.live="status" required>
                                <option value="excused">Izin</option>
                                <option value="wfh">WFH</option>
                            </x-select>
                            <x-input-error for="status" class="mt-2" />
                        </div>
                    @elseif($modalMode === 'cuti')
                        @if($leaveBalance)
                            <div class="sm:col-span-2 p-3.5 rounded-xl bg-sky-50/80 dark:bg-gray-800 border border-sky-200 dark:border-gray-700 shadow-xs">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-8 w-8 rounded-lg bg-sky-500 flex items-center justify-center text-white shadow-xs">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-xs font-semibold text-gray-800 dark:text-gray-100">Saldo Cuti Tahunan ({{ $leaveBalance->year }})</div>
                                            <div class="text-[11px] text-gray-500 dark:text-gray-400">Total Kuota: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $leaveBalance->total_quota }} hari</span> | Terpakai: <span class="font-bold text-amber-600 dark:text-amber-400">{{ $leaveBalance->used_quota }} hari</span></div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold {{
                                            $leaveBalance->remaining_quota > 2
                                                ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/90 dark:text-emerald-300 dark:border-emerald-700'
                                                : ($leaveBalance->remaining_quota > 0
                                                    ? 'bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/90 dark:text-amber-300 dark:border-amber-700'
                                                    : 'bg-rose-100 text-rose-800 border border-rose-200 dark:bg-rose-950/90 dark:text-rose-300 dark:border-rose-700')
                                        }}">
                                            Sisa: {{ $leaveBalance->remaining_quota }} Hari
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="sm:col-span-2">
                            <x-label for="status" value="{{ __('Jenis Cuti') }}" />
                            <x-select id="status" class="mt-1 block w-full" wire:model.live="status" required>
                                <option value="leave">Cuti Tahunan (Memotong Kuota Saldo Cuti)</option>
                                <option value="special-leaves">Cuti Khusus (Menikah, Melahirkan, Duka, dsb. - Tidak Memotong Kuota Tahunan)</option>
                            </x-select>
                            <x-input-error for="status" class="mt-2" />
                        </div>
                    @elseif($modalMode === 'imp')
                        <div class="sm:col-span-1">
                            <x-label for="shift_id" value="Pilih Shift" />
                            <x-select id="shift_id" wire:model="shift_id" class="mt-1 block w-full font-semibold" required>
                                <option value="">-- Pilih Shift --</option>
                                @php
                                    $userDivId = auth()->user()->division_id;
                                    $divisionShifts = $shifts->filter(fn($s) => $s->division_id == $userDivId && !is_null($s->division_id));
                                    $globalShifts = $shifts->filter(fn($s) => is_null($s->division_id));
                                @endphp

                                @if($divisionShifts->count() > 0)
                                    <optgroup label="Shift Divisi (Prioritas Utama)">
                                        @foreach($divisionShifts as $shift)
                                            @php
                                                $duration = \Carbon\Carbon::parse($shift->start_time)->diffInMinutes(\Carbon\Carbon::parse($shift->end_time));
                                                $hours = floor($duration / 60);
                                            @endphp
                                            <option value="{{ $shift->id }}">{{ $shift->name }} (Target: {{ $hours }} jam)</option>
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if($globalShifts->count() > 0)
                                    <optgroup label="Shift Global">
                                        @foreach($globalShifts as $shift)
                                            @php
                                                $duration = \Carbon\Carbon::parse($shift->start_time)->diffInMinutes(\Carbon\Carbon::parse($shift->end_time));
                                                $hours = floor($duration / 60);
                                            @endphp
                                            <option value="{{ $shift->id }}">{{ $shift->name }} (Target: {{ $hours }} jam)</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </x-select>
                            <x-input-error for="shift_id" class="mt-2" />
                        </div>
                    @endif

                    <div class="sm:col-span-1">
                        <x-label for="from">
                            <span>{{ $modalMode === 'imp' ? 'Tanggal IMP' : 'Tanggal mulai' }}</span>
                        </x-label>
                        <x-input type="date" id="from" wire:model="from"
                            min="{{ $modalMode === 'imp' ? date('Y-m-01') : date('Y-m-d') }}"
                            max="{{ $modalMode === 'imp' ? date('Y-m-t') : '' }}"
                            class="mt-1 block w-full" required />
                        <x-input-error for="from" class="mt-2" />
                    </div>

                    @if($modalMode !== 'imp')
                        <div class="sm:col-span-1">
                            <x-label for="to" value="Tanggal berakhir (Opsional)" />
                            <x-input type="date" id="to" wire:model="to"
                                min="{{ date('Y-m-d') }}"
                                class="mt-1 block w-full" />
                            <x-input-error for="to" class="mt-2" />
                        </div>
                    @else
                        <div class="sm:col-span-1">
                            <x-label for="imp_duration_minutes" value="Durasi IMP (jam:menit)" />
                            <x-input type="text"
                                     id="imp_duration_minutes"
                                     inputmode="numeric"
                                     placeholder="Contoh: 2:30 atau 0:20"
                                     maxlength="5"
                                     x-data
                                     x-on:input="
                                       let v = $el.value.replace(/\D/g, '').slice(0, 4);
                                       if (v.length >= 3) v = v.slice(0, v.length - 2) + ':' + v.slice(v.length - 2);
                                       $el.value = v;
                                       $wire.set('imp_duration_minutes', v);
                                     "
                                     value="{{ $imp_duration_minutes }}"
                                     class="mt-1 block w-full font-semibold tracking-wider"
                                     required />
                            <span class="text-[11px] text-gray-400">Format: jam:menit (contoh: 2:30 atau 0:20)</span>
                            <x-input-error for="imp_duration_minutes" class="mt-2" />
                        </div>
                    @endif

                    <div class="sm:col-span-2">
                        <x-label for="note" value="Keterangan" />
                        <x-textarea id="note" wire:model="note" class="mt-1 block w-full" required></x-textarea>
                        <x-input-error for="note" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-label for="attachment" value="Lampiran (Opsional, max 3MB)" />
                        <div class="flex items-center gap-3 mt-1">
                            <input type="file" id="attachment" wire:model="attachment" class="hidden">
                            <label for="attachment" class="inline-flex cursor-pointer items-center rounded-md bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-800 transition hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                                Pilih Lampiran
                            </label>
                            <div wire:loading wire:target="attachment" class="text-xs text-blue-500">Mengunggah...</div>
                            <span wire:loading.remove wire:target="attachment" class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                @if($attachment)
                                    {{ method_exists($attachment, 'getClientOriginalName') ? $attachment->getClientOriginalName() : 'File terpilih' }}
                                @else
                                    Tidak ada file dipilih
                                @endif
                            </span>
                        </div>
                        <x-input-error for="attachment" class="mt-2" />
                    </div>
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('isModalOpen', false)" wire:loading.attr="disabled">
                {{ __('Batal') }}
            </x-secondary-button>

            <x-button class="ml-3 bg-blue-600 hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900" wire:click="submit" wire:loading.attr="disabled">
                {{ __('Simpan') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
