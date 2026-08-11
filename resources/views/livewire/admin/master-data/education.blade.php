<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
  <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
    <thead class="bg-gray-50 dark:bg-gray-900">
      <tr>
        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
          Pendidikan
        </th>
        <th scope="col" class="relative px-6 py-3">
          <span class="sr-only">Actions</span>
        </th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
      @foreach ($educations as $education)
        <tr>
          <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
            {{ $education->name }}
          </td>
          <td class="relative flex justify-end gap-2 px-4 py-4">
            <button type="button" wire:click="edit({{ $education->id }})" title="Edit Pendidikan"
              class="inline-flex items-center justify-center rounded-md border border-transparent bg-sky-500 px-2 py-1.5 text-white shadow-sm hover:bg-sky-600 focus:outline-none transition-colors duration-150">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button type="button" wire:click="confirmDeletion({{ $education->id }}, '{{ $education->name }}')" title="Hapus Pendidikan"
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

  <x-confirmation-modal wire:model="confirmingDeletion">
    <x-slot name="title">
      Hapus Pendidikan
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
      Pendidikan Baru
    </x-slot>

    <form wire:submit="create">
      <x-slot name="content">
        <x-label for="name">Nama Pendidikan</x-label>
        <x-input id="name" class="mt-1 block w-full" type="text" wire:model="name" />
        @error('name')
          <x-input-error for="name" class="mt-2" message="{{ $message }}" />
        @enderror
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
      Edit Pendidikan
    </x-slot>

    <form wire:submit.prevent="update">
      <x-slot name="content">
        <x-label for="name">Nama Pendidikan</x-label>
        <x-input id="name" class="mt-1 block w-full" type="text" wire:model="name" />
        @error('name')
          <x-input-error for="name" class="mt-2" message="{{ $message }}" />
        @enderror
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
