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
                        <div class="sm:col-span-2">
                            <x-label for="status" value="{{ __('Jenis Cuti') }}" />
                            <x-select id="status" class="mt-1 block w-full" wire:model.live="status" required>
                                <option value="leave">Cuti</option>
                                <option value="special-leaves">Cuti Khusus</option>
                            </x-select>
                            <x-input-error for="status" class="mt-2" />
                        </div>
                    @elseif($modalMode === 'imp')
                        <div class="sm:col-span-1">
                            <x-label for="shift_id" value="Pilih Shift" />
                            <x-select id="shift_id" wire:model="shift_id" class="mt-1 block w-full" required>
                                <option value="">-- Pilih Shift --</option>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->name }} (Target: {{ floor(\Carbon\Carbon::parse($shift->start_time)->diffInMinutes(\Carbon\Carbon::parse($shift->end_time)) / 60) }} jam)</option>
                                @endforeach
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
                            <x-label for="imp_duration_minutes" value="Durasi IMP (HH:MM)" />
                            <x-input type="text" id="imp_duration_minutes" wire:model="imp_duration_minutes"
                                pattern="^[0-9]+:[0-5][0-9]$" class="mt-1 block w-full"
                                placeholder="Contoh: 1:30" required />
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
