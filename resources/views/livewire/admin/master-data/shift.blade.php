<div>

  <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 whitespace-nowrap">
            Shift
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 whitespace-nowrap">
            {{ __('Time Start') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 whitespace-nowrap">
            {{ __('Time End') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 whitespace-nowrap">
            Divisi
          </th>
          <th scope="col" class="relative px-6 py-3">
            <span class="sr-only">Actions</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
        @foreach ($shifts as $shift)
          <tr>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
              {{ $shift->name }}
            </td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
              {{ $shift->start_time }}
            </td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
              {{ $shift->end_time ?? '-' }}
            </td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
              @if($shift->division)
                <span class="inline-flex items-center rounded-md bg-blue-50 dark:bg-blue-900/40 px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10">
                  {{ $shift->division->name }}
                </span>
              @else
                <span class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-700 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-300">
                  Global (Semua Divisi)
                </span>
              @endif
            </td>
            <td class="relative flex justify-end gap-2 px-4 py-4 whitespace-nowrap">
              <button type="button" wire:click="edit({{ $shift->id }})" title="Edit Shift"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-sky-500 px-2 py-1.5 text-white shadow-sm hover:bg-sky-600 focus:outline-none transition-colors duration-150">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button type="button" wire:click="confirmDeletion({{ $shift->id }}, '{{ $shift->name }}')" title="Hapus Shift"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-2 py-1.5 text-white shadow-sm hover:bg-red-700 focus:outline-none transition-colors duration-150">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <x-confirmation-modal wire:model="confirmingDeletion">
    <x-slot name="title">
      Hapus Shift
    </x-slot>

    <x-slot name="content">
      Apakah Anda yakin ingin menghapus <b>{{ $deleteName }}</b>?
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">
        {{ __('Cancel') }}
      </x-secondary-button>

      <x-danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">
        {{ __('Confirm') }}
      </x-danger-button>
    </x-slot>
  </x-confirmation-modal>

  <x-dialog-modal wire:model="creating">
    <x-slot name="title">
      Shift Baru
    </x-slot>

    <form wire:submit.prevent="create">
      <x-slot name="content">
        <div>
          <x-label for="name">Nama Shift</x-label>
          <x-input id="name" class="mt-1 block w-full" type="text" wire:model="form.name" />
          @error('form.name')
            <x-input-error for="form.name" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="start_time">{{ __('Time Start') }}</x-label>
            <x-input id="start_time" class="mt-1 block w-full" type="time" wire:model="form.start_time" required />
            @error('form.start_time')
              <x-input-error for="form.start_time" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="end_time">{{ __('Time End') }}</x-label>
            <x-input id="end_time" class="mt-1 block w-full" type="time" wire:model="form.end_time" />
            @error('form.end_time')
              <x-input-error for="form.end_time" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>

        <div class="mt-4">
          @if(auth()->user()->isSuperadmin)
            <x-label for="division_id">Divisi Scope</x-label>
            <x-select id="division_id" wire:model="form.division_id" class="mt-1 block w-full">
              <option value="">Global (Semua Divisi)</option>
              @foreach ($divisions as $division)
                <option value="{{ $division->id }}">{{ $division->name }}</option>
              @endforeach
            </x-select>
            @error('form.division_id')
              <x-input-error for="form.division_id" class="mt-2" message="{{ $message }}" />
            @enderror
          @else
            <x-label>Divisi Scope</x-label>
            <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-900 rounded-md text-sm text-gray-700 dark:text-gray-300 font-medium">
              {{ auth()->user()->division?->name ?? 'Divisi Anda' }} (Otomatis)
            </div>
          @endif
        </div>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$toggle('creating')" wire:loading.attr="disabled">
          {{ __('Cancel') }}
        </x-secondary-button>

        <x-button class="ml-2" wire:click="create" wire:loading.attr="disabled">
          {{ __('Confirm') }}
        </x-button>
      </x-slot>
    </form>
  </x-dialog-modal>

  <x-dialog-modal wire:model="editing">
    <x-slot name="title">
      Edit Shift
    </x-slot>

    <form wire:submit.prevent="update" id="shift-edit">
      <x-slot name="content">
        <div>
          <x-label for="name">Nama Shift</x-label>
          <x-input id="name" class="mt-1 block w-full" type="text" wire:model="form.name" />
          @error('form.name')
            <x-input-error for="form.name" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="start_time">{{ __('Time Start') }}</x-label>
            <x-input id="start_time" class="mt-1 block w-full" type="time" wire:model="form.start_time" required />
            @error('form.start_time')
              <x-input-error for="form.start_time" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="end_time">{{ __('Time End') }}</x-label>
            <x-input id="end_time" class="mt-1 block w-full" type="time" wire:model="form.end_time" />
            @error('form.end_time')
              <x-input-error for="form.end_time" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>

        <div class="mt-4">
          @if(auth()->user()->isSuperadmin)
            <x-label for="edit_division_id">Divisi Scope</x-label>
            <x-select id="edit_division_id" wire:model="form.division_id" class="mt-1 block w-full">
              <option value="">Global (Semua Divisi)</option>
              @foreach ($divisions as $division)
                <option value="{{ $division->id }}">{{ $division->name }}</option>
              @endforeach
            </x-select>
            @error('form.division_id')
              <x-input-error for="form.division_id" class="mt-2" message="{{ $message }}" />
            @enderror
          @else
            <x-label>Divisi Scope</x-label>
            <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-900 rounded-md text-sm text-gray-700 dark:text-gray-300 font-medium">
              {{ auth()->user()->division?->name ?? 'Divisi Anda' }} (Otomatis)
            </div>
          @endif
        </div>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$toggle('editing')" wire:loading.attr="disabled">
          {{ __('Cancel') }}
        </x-secondary-button>

        <x-button class="ml-2" wire:click="update" wire:loading.attr="disabled">
          {{ __('Confirm') }}
        </x-button>
      </x-slot>
    </form>
  </x-dialog-modal>
</div>
