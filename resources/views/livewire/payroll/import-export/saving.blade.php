<div>
  <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:gap-6">
    @if ($mode != 'import')
      <div>
        <h3 class="mb-4 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Ekspor Data Master Syirkah
        </h3>
        <form wire:submit.prevent="export">
          <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Klik tombol di bawah ini untuk mengunduh seluruh data Master Syirkah (Saving) dalam format Excel.
          </p>
          <div class="mt-4 flex flex-col items-center justify-stretch gap-4">
            <x-secondary-button type="button" wire:click="preview" class="w-full justify-center">
              @if ($mode == 'export')
                {{ __('Cancel') }}
              @else
                {{ __('Preview') }}
              @endif
            </x-secondary-button>
            <x-button wire:click="export" class="w-full justify-center">
              {{ $mode == 'export' ? __('Confirm & Export') : __('Export') }}
            </x-button>
          </div>
        </form>
      </div>
    @endif
    @if ($mode != 'export')
      <div>
        <h3 class="mb-4 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Impor Data Master Syirkah
        </h3>
        <form x-data="{ file: null }" method="post" wire:submit.prevent="import" enctype="multipart/form-data">
          @csrf
          <div class="mb-4 flex items-center gap-3">
            <x-secondary-button class="me-2" type="button" x-on:click.prevent="$refs.file.click()"
              x-text="file ? 'Ganti File' : 'Pilih File dan Pratinjau'">
              Pilih File
            </x-secondary-button>
            <x-secondary-button class="me-2" type="button" x-show="file"
              x-on:click.prevent="$refs.file.files[0] = null; file = null; $wire.$set('file', null)">
              Hapus File
            </x-secondary-button>
            <h5 class="text-sm dark:text-gray-200" x-text="file ? file.name : 'File Belum Dipilih'"></h5>
            <x-input type="file" class="hidden" name="file" x-ref="file"
              x-on:change="file = $refs.file.files[0]" wire:model.live="file" />
          </div>
          <div class="flex items-center justify-stretch">
            <x-success-button class="w-full"
              x-text="file ? '{{ __('Confirm & Import') }} ' + file.name : '{{ __('Import') }}'">
            </x-success-button>
          </div>
        </form>
      </div>
    @endif
  </div>
  @if ($mode && $previewing)
    <h3 class="mt-4 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Preview') . ' ' . $mode }}
    </h3>
    <div class="mt-4 w-full overflow-x-scroll text-sm">
      @php
        $trClass = 'divide-x divide-gray-200 dark:divide-gray-700';
        $thClass = 'px-4 py-3 text-left font-semibold dark:text-white whitespace-nowrap';
        $tdClass = 'px-4 py-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap';
      @endphp
      <table class="w-full divide-y divide-gray-200 border dark:divide-gray-700 dark:border-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr class="{{ $trClass }}">
            <th scope="col" class="px-2 py-3 text-left font-semibold dark:text-white">No</th>
            <th scope="col" class="{{ $thClass }}">Savings Name</th>
            <th scope="col" class="{{ $thClass }}">Mandatory Savings</th>
            <th scope="col" class="{{ $thClass }}">Secondary Savings</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
          @foreach ($savings as $saving)
            <tr class="{{ $trClass }}">
              <td class="px-2 py-4 text-center text-sm font-medium text-gray-900 dark:text-white">
                {{ $loop->iteration }}
              </td>
              <td class="{{ $tdClass }}">{{ $saving->savings_name }}</td>
              <td class="{{ $tdClass }}">{{ $saving->mandatory_savings }}</td>
              <td class="{{ $tdClass }}">{{ $saving->secondary_savings }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
