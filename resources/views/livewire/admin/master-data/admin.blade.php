<div>

  <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th scope="col" class="relative px-2 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300">
            No.
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Name') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Email') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Group') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            Divisi Scope
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Phone Number') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Telegram / Chat ID') }}
          </th>
          <th scope="col" class="relative px-6 py-3">
            <span class="sr-only">Actions</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
        @php
          $class = 'cursor-pointer group-hover:bg-gray-100 dark:group-hover:bg-gray-700';
        @endphp
        @foreach ($users as $user)
          @php
            $wireClick = "wire:click=show('$user->id')";
          @endphp
          <tr wire:key="{{ $user->id }}" class="group">
            <td class="{{ $class }} p-2 text-center text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              {{ $loop->iteration }}
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              {{ $user->name }}
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              {{ $user->email }}
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold uppercase {{ $user->group === 'superadmin' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' : ($user->group === 'owner' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : ($user->group === 'syirkah' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300')) }}">
                {{ $user->group }}
              </span>
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              @if ($user->group === 'superadmin')
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                  Semua Divisi
                </span>
              @elseif ($user->adminDivisions && $user->adminDivisions->isNotEmpty())
                <div class="flex flex-wrap gap-1 max-w-xs">
                  @foreach ($user->adminDivisions as $ad)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/50 dark:border-sky-800 dark:text-sky-300">
                      {{ $ad->name }}
                    </span>
                  @endforeach
                </div>
              @elseif ($user->division)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/50 dark:border-sky-800 dark:text-sky-300">
                  {{ $user->division->name }}
                </span>
              @else
                <span class="text-gray-400 text-xs">-</span>
              @endif
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              {{ $user->phone }}
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              @if($user->telegram || $user->chat_code)
                <div class="flex flex-col">
                  @if($user->telegram)
                    <span class="font-bold text-sky-600 dark:text-sky-400 text-xs">{{ str_starts_with($user->telegram, '@') ? $user->telegram : '@' . $user->telegram }}</span>
                  @endif
                  @if($user->chat_code)
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 font-mono">ID: {{ $user->chat_code }}</span>
                  @endif
                </div>
              @else
                <span class="text-gray-400 text-xs">-</span>
              @endif
            </td>
            <td class="relative flex justify-center gap-2 px-4 py-4">
              @if (Auth::user()->isSuperadmin || Auth::user()->id == $user->id)
                <button type="button" wire:click="edit('{{ $user->id }}')" title="Edit Admin"
                  class="inline-flex items-center justify-center rounded-md border border-transparent bg-sky-500 px-2 py-1.5 text-white shadow-sm hover:bg-sky-600 focus:outline-none transition-colors duration-150">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                @if (Auth::user()->isSuperadmin && Auth::user()->id !== $user->id)
                  <button type="button" wire:click="confirmDeletion('{{ $user->id }}', '{{ $user->name }}')" title="Hapus Admin"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-2 py-1.5 text-white shadow-sm hover:bg-red-700 focus:outline-none transition-colors duration-150">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                @endif
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="mt-3">
    {{ $users->links() }}
  </div>

  <x-confirmation-modal wire:model="confirmingDeletion">
    <x-slot name="title">
      Hapus Admin
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

  <!-- CREATE ADMIN MODAL -->
  <x-dialog-modal wire:model="creating">
    <x-slot name="title">
      Admin Baru
    </x-slot>

    <form wire:submit="create">
      <x-slot name="content">
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
          <div x-data="{ photoName: null, photoPreview: null }" class="flex flex-col items-center text-center">
            <!-- Profile Photo File Input -->
            <input type="file" id="photo" class="hidden" wire:model.live="form.photo" x-ref="photo"
              x-on:change="
                                    photoName = $refs.photo.files[0].name;
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        photoPreview = e.target.result;
                                    };
                                    reader.readAsDataURL($refs.photo.files[0]);
                            " />

            <x-label for="photo" value="{{ __('Photo') }}" />

            <!-- Current Profile Photo -->
            <div class="mt-2 h-20 w-20 rounded-full outline outline-gray-400" x-show="! photoPreview">
            </div>

            <!-- New Profile Photo Preview -->
            <div class="mt-2" x-show="photoPreview" style="display: none;">
              <span class="block h-20 w-20 rounded-full bg-cover bg-center bg-no-repeat"
                x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
              </span>
            </div>

            <div class="mt-2 flex space-x-2">
              <x-secondary-button type="button" x-on:click.prevent="$refs.photo.click()">
                {{ __('Select A New Photo') }}
              </x-secondary-button>

              @if ($form->user?->profile_photo_path)
                <x-secondary-button type="button" wire:click="deleteProfilePhoto">
                  {{ __('Remove Photo') }}
                </x-secondary-button>
              @endif
            </div>

            @error('form.photo')
              <x-input-error for="form.photo" message="{{ $message }}" class="mt-2" />
            @enderror
          </div>
        @endif
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="name">Nama Admin</x-label>
            <x-input id="name" class="mt-1 block w-full" type="text" wire:model="form.name" />
            @error('form.name')
              <x-input-error for="form.name" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="nip">NIP</x-label>
            <x-input id="nip" class="mt-1 block w-full" type="text" wire:model="form.nip"
              placeholder="12345678" required />
            @error('form.nip')
              <x-input-error for="form.nip" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="email">{{ __('Email') }}</x-label>
            <x-input id="email" class="mt-1 block w-full" type="email" wire:model="form.email"
              placeholder="example@example.com" required />
            @error('form.email')
              <x-input-error for="form.email" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="password">{{ __('Password') }}</x-label>
            <div x-data="{ show: false }" class="relative mt-1">
              <x-input id="password" class="block w-full pr-10" x-bind:type="show ? 'text' : 'password'" wire:model="form.password"
                placeholder="New Password" required />
              <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3">
                <x-heroicon-o-eye x-show="!show" class="h-5 w-5 text-gray-400 hover:text-gray-600" />
                <x-heroicon-o-eye-slash x-show="show" class="h-5 w-5 text-gray-400 hover:text-gray-600" style="display: none;" />
              </button>
            </div>
            <p class="mt-1 text-sm dark:text-gray-400">Default password: <b>admin</b></p>
            @error('form.password')
              <x-input-error for="form.password" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="phone">{{ __('Phone') }}</x-label>
            <x-input id="phone" class="mt-1 block w-full" type="text" wire:model="form.phone"
              placeholder="+628123456789" />
            @error('form.phone')
              <x-input-error for="form.phone" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="city">{{ __('City') }}</x-label>
            <x-input id="city" class="mt-1 block w-full" type="text" wire:model="form.city"
              placeholder="Domisili" />
            @error('form.city')
              <x-input-error for="form.city" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>

        <!-- Telegram Username & Chat ID Row -->
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="telegram">Username Telegram</x-label>
            <x-input id="telegram" class="mt-1 block w-full" type="text" wire:model="form.telegram"
              placeholder="@username (Opsional)" />
            @error('form.telegram')
              <x-input-error for="form.telegram" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="chat_code">Telegram Chat ID / Code</x-label>
            <x-input id="chat_code" class="mt-1 block w-full" type="text" wire:model="form.chat_code"
              placeholder="Contoh: 1909551792 (Opsional)" />
            @error('form.chat_code')
              <x-input-error for="form.chat_code" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>

        @if ($form->group === 'admin')
          <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
            <div class="w-full sm:w-1/2">
              <x-label for="form.group" value="{{ __('Group') }}" />
              <x-select id="form.group" class="mt-1 block w-full" wire:model.live="form.group" required>
                @foreach ($groups as $group)
                  @if ($group != 'user')
                    <option value="{{ $group }}" {{ $group == $form->group ? 'selected' : '' }}>
                      {{ $group }}
                    </option>
                  @endif
                @endforeach
              </x-select>
              @error('form.group')
                <x-input-error for="form.group" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
            <div class="w-full sm:w-1/2">
              <x-label for="form.job_title_id" value="{{ __('Job Title') }}" />
              <x-select id="form.job_title_id" class="mt-1 block w-full" wire:model="form.job_title_id">
                <option value="">{{ __('Select Job Title') }}</option>
                @foreach (App\Models\JobTitle::all() as $jobTitle)
                  <option value="{{ $jobTitle->id }}" {{ $jobTitle->id == $form->job_title_id ? 'selected' : '' }}>
                    {{ $jobTitle->name }}
                  </option>
                @endforeach
              </x-select>
              @error('form.job_title_id')
                <x-input-error for="form.job_title_id" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
          </div>

          <!-- Multi-Division Selector for Admin -->
          <div class="mt-4">
            <div class="flex items-center justify-between mb-2">
              <x-label value="Scope Divisi (Bisa pilih lebih dari 1)" class="font-semibold text-gray-700 dark:text-gray-200" />
              <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-0.5 rounded-full font-bold {{ count($form->division_ids) > 0 ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/80 dark:text-sky-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                  {{ count($form->division_ids) }} dipilih
                </span>
                <button type="button" wire:click="$set('form.division_ids', {{ json_encode(App\Models\Division::pluck('id')->toArray()) }})" class="text-[11px] text-sky-600 hover:text-sky-700 dark:text-sky-400 font-semibold hover:underline">
                  Pilih Semua
                </button>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <button type="button" wire:click="$set('form.division_ids', [])" class="text-[11px] text-red-500 hover:text-red-600 dark:text-red-400 font-semibold hover:underline">
                  Hapus Semua
                </button>
              </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 max-h-52 overflow-y-auto custom-scrollbar-y">
              @foreach (App\Models\Division::orderBy('name')->get() as $division)
                @php
                  $isSelected = in_array((int)$division->id, array_map('intval', (array)$form->division_ids), true);
                @endphp
                <label class="flex items-center gap-2.5 p-2 rounded-lg border transition cursor-pointer select-none {{ $isSelected ? 'bg-sky-50/90 border-sky-300 text-sky-900 dark:bg-sky-950/60 dark:border-sky-700 dark:text-sky-200 ring-1 ring-sky-400/30' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-100/70 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700/60' }}">
                  <input type="checkbox"
                         value="{{ $division->id }}"
                         wire:model="form.division_ids"
                         class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-700 cursor-pointer">
                  <span class="text-xs font-medium truncate">{{ $division->name }}</span>
                </label>
              @endforeach
            </div>
            @error('form.division_ids')
              <x-input-error for="form.division_ids" class="mt-2" message="{{ $message }}" />
            @enderror
            @error('form.division_ids.*')
              <x-input-error for="form.division_ids.*" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        @else
          <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
            <div class="w-full sm:w-1/3">
              <x-label for="form.group" value="{{ __('Group') }}" />
              <x-select id="form.group" class="mt-1 block w-full" wire:model.live="form.group" required>
                @foreach ($groups as $group)
                  @if ($group != 'user')
                    <option value="{{ $group }}" {{ $group == $form->group ? 'selected' : '' }}>
                      {{ $group }}
                    </option>
                  @endif
                @endforeach
              </x-select>
              @error('form.group')
                <x-input-error for="form.group" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
            <div class="w-full sm:w-1/3">
              <x-label for="form.division_id" value="{{ __('Division') }}" />
              <x-select id="form.division_id" class="mt-1 block w-full" wire:model="form.division_id">
                <option value="">{{ __('Select Division') }}</option>
                @foreach (App\Models\Division::all() as $division)
                  <option value="{{ $division->id }}" {{ $division->id == $form->division_id ? 'selected' : '' }}>
                    {{ $division->name }}
                  </option>
                @endforeach
              </x-select>
              @error('form.division_id')
                <x-input-error for="form.division_id" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
            <div class="w-full sm:w-1/3">
              <x-label for="form.job_title_id" value="{{ __('Job Title') }}" />
              <x-select id="form.job_title_id" class="mt-1 block w-full" wire:model="form.job_title_id">
                <option value="">{{ __('Select Job Title') }}</option>
                @foreach (App\Models\JobTitle::all() as $jobTitle)
                  <option value="{{ $jobTitle->id }}" {{ $jobTitle->id == $form->job_title_id ? 'selected' : '' }}>
                    {{ $jobTitle->name }}
                  </option>
                @endforeach
              </x-select>
              @error('form.job_title_id')
                <x-input-error for="form.job_title_id" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
          </div>
        @endif
        <div class="mt-4">
          <x-label for="address">{{ __('Address') }}</x-label>
          <x-input id="address" class="mt-1 block w-full" type="text" wire:model="form.address"
            placeholder="Jl. Jend. Sudirman" />
          @error('form.address')
            <x-input-error for="form.address" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$toggle('creating')" wire:loading.attr="disabled">
          {{ __('Cancel') }}
        </x-secondary-button>

        <x-button class="ml-2" wire:click="create" wire:loading.attr="disabled" wire:target="form.photo">
          {{ __('Confirm') }}
        </x-button>
      </x-slot>
    </form>
  </x-dialog-modal>

  <!-- EDIT ADMIN MODAL -->
  <x-dialog-modal wire:model="editing">
    <x-slot name="title">
      Edit Admin
    </x-slot>

    <form wire:submit.prevent="update" id="user-edit">
      <x-slot name="content">
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
          <div x-data="{ photoName: null, photoPreview: null }" class="flex flex-col items-center text-center">
            <!-- Profile Photo File Input -->
            <input type="file" id="photo" class="hidden" wire:model.live="form.photo" x-ref="photo"
              x-on:change="
                                    photoName = $refs.photo.files[0].name;
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        photoPreview = e.target.result;
                                    };
                                    reader.readAsDataURL($refs.photo.files[0]);
                            " />

            <x-label for="photo" value="{{ __('Photo') }}" />

            <!-- Current Profile Photo -->
            <div class="mt-2" x-show="! photoPreview">
              <img src="{{ $form->user?->profile_photo_url }}" alt="{{ $form->user?->name }}"
                class="h-20 w-20 rounded-full object-cover outline outline-gray-400">
            </div>

            <!-- New Profile Photo Preview -->
            <div class="mt-2" x-show="photoPreview" style="display: none;">
              <span class="block h-20 w-20 rounded-full bg-cover bg-center bg-no-repeat"
                x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
              </span>
            </div>

            <div class="mt-2 flex space-x-2">
              <x-secondary-button type="button" x-on:click.prevent="$refs.photo.click()">
                {{ __('Select A New Photo') }}
              </x-secondary-button>

              @if ($form->user?->profile_photo_path)
                <x-secondary-button type="button" wire:click="deleteProfilePhoto">
                  {{ __('Remove Photo') }}
                </x-secondary-button>
              @endif
            </div>

            @error('form.photo')
              <x-input-error for="form.photo" message="{{ $message }}" class="mt-2" />
            @enderror
          </div>
        @endif
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="name">Nama Admin</x-label>
            <x-input id="name" class="mt-1 block w-full" type="text" wire:model="form.name" />
            @error('form.name')
              <x-input-error for="form.name" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="nip">NIP</x-label>
            <x-input id="nip" class="mt-1 block w-full" type="text" wire:model="form.nip"
              placeholder="12345678" required />
            @error('form.nip')
              <x-input-error for="form.nip" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="email">{{ __('Email') }}</x-label>
            <x-input id="email" class="mt-1 block w-full" type="email" wire:model="form.email"
              placeholder="example@example.com" required />
            @error('form.email')
              <x-input-error for="form.email" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="password">{{ __('Password') }}</x-label>
            <div x-data="{ show: false }" class="relative mt-1">
              <x-input id="password" class="block w-full pr-10" x-bind:type="show ? 'text' : 'password'" wire:model="form.password"
                placeholder="New Password" />
              <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3">
                <x-heroicon-o-eye x-show="!show" class="h-5 w-5 text-gray-400 hover:text-gray-600" />
                <x-heroicon-o-eye-slash x-show="show" class="h-5 w-5 text-gray-400 hover:text-gray-600" style="display: none;" />
              </button>
            </div>
            @error('form.password')
              <x-input-error for="form.password" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="phone">{{ __('Phone') }}</x-label>
            <x-input id="phone" class="mt-1 block w-full" type="text" wire:model="form.phone"
              placeholder="+628123456789" />
            @error('form.phone')
              <x-input-error for="form.phone" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="city">{{ __('City') }}</x-label>
            <x-input id="city" class="mt-1 block w-full" type="text" wire:model="form.city"
              placeholder="Domisili" />
            @error('form.city')
              <x-input-error for="form.city" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>

        <!-- Telegram Username & Chat ID Row -->
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="edit_telegram">Username Telegram</x-label>
            <x-input id="edit_telegram" class="mt-1 block w-full" type="text" wire:model="form.telegram"
              placeholder="@username (Opsional)" />
            @error('form.telegram')
              <x-input-error for="form.telegram" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="edit_chat_code">Telegram Chat ID / Code</x-label>
            <x-input id="edit_chat_code" class="mt-1 block w-full" type="text" wire:model="form.chat_code"
              placeholder="Contoh: 1909551792 (Opsional)" />
            @error('form.chat_code')
              <x-input-error for="form.chat_code" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>

        @if ($form->group === 'admin')
          <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
            <div class="w-full sm:w-1/2">
              <x-label for="form.group" value="{{ __('Group') }}" />
              <x-select id="form.group" class="mt-1 block w-full" wire:model.live="form.group" required>
                @foreach ($groups as $group)
                  @if ($group != 'user')
                    <option value="{{ $group }}" {{ $group == $form->group ? 'selected' : '' }}>
                      {{ $group }}
                    </option>
                  @endif
                @endforeach
              </x-select>
              @error('form.group')
                <x-input-error for="form.group" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
            <div class="w-full sm:w-1/2">
              <x-label for="form.job_title_id" value="{{ __('Job Title') }}" />
              <x-select id="form.job_title_id" class="mt-1 block w-full" wire:model="form.job_title_id">
                <option value="">{{ __('Select Job Title') }}</option>
                @foreach (App\Models\JobTitle::all() as $jobTitle)
                  <option value="{{ $jobTitle->id }}" {{ $jobTitle->id == $form->job_title_id ? 'selected' : '' }}>
                    {{ $jobTitle->name }}
                  </option>
                @endforeach
              </x-select>
              @error('form.job_title_id')
                <x-input-error for="form.job_title_id" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
          </div>

          <!-- Multi-Division Selector for Admin -->
          <div class="mt-4">
            <div class="flex items-center justify-between mb-2">
              <x-label value="Scope Divisi (Bisa pilih lebih dari 1)" class="font-semibold text-gray-700 dark:text-gray-200" />
              <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-0.5 rounded-full font-bold {{ count($form->division_ids) > 0 ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/80 dark:text-sky-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                  {{ count($form->division_ids) }} dipilih
                </span>
                <button type="button" wire:click="$set('form.division_ids', {{ json_encode(App\Models\Division::pluck('id')->toArray()) }})" class="text-[11px] text-sky-600 hover:text-sky-700 dark:text-sky-400 font-semibold hover:underline">
                  Pilih Semua
                </button>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <button type="button" wire:click="$set('form.division_ids', [])" class="text-[11px] text-red-500 hover:text-red-600 dark:text-red-400 font-semibold hover:underline">
                  Hapus Semua
                </button>
              </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 max-h-52 overflow-y-auto custom-scrollbar-y">
              @foreach (App\Models\Division::orderBy('name')->get() as $division)
                @php
                  $isSelected = in_array((int)$division->id, array_map('intval', (array)$form->division_ids), true);
                @endphp
                <label class="flex items-center gap-2.5 p-2 rounded-lg border transition cursor-pointer select-none {{ $isSelected ? 'bg-sky-50/90 border-sky-300 text-sky-900 dark:bg-sky-950/60 dark:border-sky-700 dark:text-sky-200 ring-1 ring-sky-400/30' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-100/70 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700/60' }}">
                  <input type="checkbox"
                         value="{{ $division->id }}"
                         wire:model="form.division_ids"
                         class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-700 cursor-pointer">
                  <span class="text-xs font-medium truncate">{{ $division->name }}</span>
                </label>
              @endforeach
            </div>
            @error('form.division_ids')
              <x-input-error for="form.division_ids" class="mt-2" message="{{ $message }}" />
            @enderror
            @error('form.division_ids.*')
              <x-input-error for="form.division_ids.*" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        @else
          <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
            <div class="w-full sm:w-1/3">
              <x-label for="form.group" value="{{ __('Group') }}" />
              <x-select id="form.group" class="mt-1 block w-full" wire:model.live="form.group" required>
                @foreach ($groups as $group)
                  @if ($group != 'user')
                    <option value="{{ $group }}" {{ $group == $form->group ? 'selected' : '' }}>
                      {{ $group }}
                    </option>
                  @endif
                @endforeach
              </x-select>
              @error('form.group')
                <x-input-error for="form.group" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
            <div class="w-full sm:w-1/3">
              <x-label for="form.division_id" value="{{ __('Division') }}" />
              <x-select id="form.division_id" class="mt-1 block w-full" wire:model="form.division_id">
                <option value="">{{ __('Select Division') }}</option>
                @foreach (App\Models\Division::all() as $division)
                  <option value="{{ $division->id }}" {{ $division->id == $form->division_id ? 'selected' : '' }}>
                    {{ $division->name }}
                  </option>
                @endforeach
              </x-select>
              @error('form.division_id')
                <x-input-error for="form.division_id" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
            <div class="w-full sm:w-1/3">
              <x-label for="form.job_title_id" value="{{ __('Job Title') }}" />
              <x-select id="form.job_title_id" class="mt-1 block w-full" wire:model="form.job_title_id">
                <option value="">{{ __('Select Job Title') }}</option>
                @foreach (App\Models\JobTitle::all() as $jobTitle)
                  <option value="{{ $jobTitle->id }}" {{ $jobTitle->id == $form->job_title_id ? 'selected' : '' }}>
                    {{ $jobTitle->name }}
                  </option>
                @endforeach
              </x-select>
              @error('form.job_title_id')
                <x-input-error for="form.job_title_id" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
          </div>
        @endif
        <div class="mt-4">
          <x-label for="address">{{ __('Address') }}</x-label>
          <x-input id="address" class="mt-1 block w-full" type="text" wire:model="form.address"
            placeholder="Jl. Jend. Sudirman" />
          @error('form.address')
            <x-input-error for="form.address" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$toggle('editing')" wire:loading.attr="disabled">
          {{ __('Cancel') }}
        </x-secondary-button>

        <x-button class="ml-2" wire:click="update" wire:loading.attr="disabled" wire:target="form.photo">
          {{ __('Confirm') }}
        </x-button>
      </x-slot>
    </form>
  </x-dialog-modal>

  <!-- SHOW DETAIL MODAL -->
  <x-modal wire:model="showDetail">
    @if ($form->user)
      @php
        $jobTitle = $form->user->jobTitle ? (is_string($form->user->jobTitle) ? json_decode($form->user->jobTitle)?->name : $form->user->jobTitle?->name) : '-';
        $education = $form->user->education ? (is_string($form->user->education) ? json_decode($form->user->education)?->name : $form->user->education?->name) : '-';
      @endphp
      <div class="px-6 py-4">
        <div class="my-4 flex items-center justify-center">
          <img class="h-32 w-32 rounded-full object-cover" src="{{ $form->user->profile_photo_url }}"
            alt="{{ $form->user->name }}" title="{{ $form->user->name }}" />
        </div>

        <div class="text-center text-lg font-medium text-gray-900 dark:text-gray-100">
          {{ $form->user->name }}
        </div>

        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
          <div class="mt-4">
            <x-label for="nip" value="NIP" />
            <p>{{ $form->user->nip }}</p>
          </div>
          <div class="mt-4">
            <x-label for="email" value="{{ __('Email') }}" />
            <p>{{ $form->user->email }}</p>
          </div>
          <div class="mt-4">
            <x-label for="phone" value="{{ __('Phone') }}" />
            <p>{{ $form->user->phone }}</p>
          </div>
          <div class="mt-4">
            <x-label for="telegram_detail" value="Telegram" />
            <p class="font-semibold text-sky-600 dark:text-sky-400">{{ $form->user->telegram ? (str_starts_with($form->user->telegram, '@') ? $form->user->telegram : '@' . $form->user->telegram) : '-' }}</p>
          </div>
          <div class="mt-4">
            <x-label for="chat_code_detail" value="Telegram Chat ID" />
            <p class="font-mono text-gray-700 dark:text-gray-300">{{ $form->user->chat_code ?: '-' }}</p>
          </div>
          <div class="mt-4">
            <x-label for="group" value="{{ __('Group') }}" />
            <p>{{ __($form->user->group) }}</p>
          </div>
          <div class="mt-4">
            <x-label for="birth_date" value="{{ __('Birth Date') }}" />
            @if ($form->user->birth_date)
              <p>{{ \Illuminate\Support\Carbon::parse($form->user->birth_date)->format('D d M Y') }}</p>
            @else
              <p>-</p>
            @endif
          </div>
          <div class="mt-4">
            <x-label for="birth_place" value="{{ __('Birth Place') }}" />
            <p>{{ $form->user->birth_place ?? '-' }}</p>
          </div>
          <div class="mt-4">
            <x-label for="address" value="{{ __('Address') }}" />
            @if (empty($form->user->address))
              <p>-</p>
            @else
              <p>{{ $form->user->address }}</p>
            @endif
          </div>
          <div class="mt-4">
            <x-label for="city" value="{{ __('City') }}" />
            @if (empty($form->user->city))
              <p>-</p>
            @else
              <p>{{ $form->user->city }}</p>
            @endif
          </div>
          <div class="mt-4">
            <x-label for="job_title_id" value="{{ __('Job Title') }}" />
            <p>{{ $jobTitle ?: '-' }}</p>
          </div>
          <div class="mt-4">
            <x-label value="Scope Divisi" />
            @if ($form->user->group === 'superadmin')
              <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">
                Semua Divisi (Superadmin)
              </span>
            @elseif ($form->user->adminDivisions && $form->user->adminDivisions->isNotEmpty())
              <div class="flex flex-wrap gap-1.5 mt-1">
                @foreach ($form->user->adminDivisions as $div)
                  <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                    {{ $div->name }}
                  </span>
                @endforeach
              </div>
            @elseif ($form->user->division)
              <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                {{ is_string($form->user->division) ? (json_decode($form->user->division)?->name ?? '-') : ($form->user->division->name ?? '-') }}
              </span>
            @else
              <p class="text-gray-400 text-xs">-</p>
            @endif
          </div>
        </div>
      </div>
    @endif
  </x-modal>

  <!-- TELEGRAM WEBHOOK & BOT GROUP TESTER MODAL -->
  <x-dialog-modal wire:model="showTelegramModal" maxWidth="3xl">
    <x-slot name="title">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="p-2 rounded-xl bg-sky-500/10 text-sky-500 dark:bg-sky-500/20">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
              Test Webhook CetakiaBot ke Grup Telegram
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Uji coba pengiriman notifikasi khusus ke grup <b>CG Bank transfer Request</b>
            </p>
          </div>
        </div>
        <button type="button" wire:click="closeTelegramTester" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </x-slot>

    <x-slot name="content">
      <div class="space-y-4">
        <!-- Error Alert -->
        @if ($telegramErrorMessage)
          <div class="p-3.5 rounded-xl bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-xs flex items-start gap-2.5">
            <svg class="w-5 h-5 shrink-0 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="flex-1">
              <p class="font-semibold">Perhatian</p>
              <p class="mt-0.5">{{ $telegramErrorMessage }}</p>
            </div>
          </div>
        @endif

        <!-- Target Group & Config Card -->
        <div class="p-4 rounded-xl border border-sky-100 dark:border-sky-900/60 bg-sky-50/50 dark:bg-sky-950/20 space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-300">
                👥 Target: Grup Telegram (Bukan Akun Users)
              </span>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                Bot: <b>CetakiaBot</b> (<code>@cetakia_bot</code>)
              </span>
            </div>
            <div class="flex items-center gap-1.5">
              <button type="button" wire:click="checkWebhookInfo" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-sky-700 dark:text-sky-300 bg-white dark:bg-gray-800 border border-sky-200 dark:border-sky-800 rounded-lg hover:bg-sky-50 dark:hover:bg-gray-700 transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Cek Webhook Info</span>
              </button>
            </div>
          </div>

          <!-- Target Group Chat ID Input -->
          <div>
            <x-label for="telegramTargetGroup" value="Telegram Group Chat ID (Khusus Grup, format awalan minus: -100xxxxxxxxxx)" class="text-xs font-semibold" />
            <div class="mt-1 flex gap-2">
              <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                  <span class="font-mono text-sm">#</span>
                </div>
                <x-input id="telegramTargetGroup" type="text" wire:model="telegramTargetGroup"
                  class="w-full pl-8 font-mono text-sm" placeholder="Contoh: -1002345678901 atau -123456789" />
              </div>
              @if ($telegramTargetGroup)
                <button type="button" wire:click="saveAsDefaultGroup" wire:loading.attr="disabled"
                  class="px-3 py-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900 transition flex items-center gap-1 shrink-0"
                  title="Simpan sebagai Chat ID grup transfer bank default">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span>Simpan Default</span>
                </button>
              @endif
            </div>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
              * Group Chat ID wajib diawali tanda minus (<code>-</code>). Telegram secara otomatis menetapkan ID negatif untuk semua grup dan supergroup.
            </p>
          </div>

          <!-- Known Groups Quick Select -->
          @if (!empty($telegramKnownGroups))
            <div class="pt-2 border-t border-sky-100 dark:border-sky-900/40">
              <span class="text-[11px] font-medium text-gray-600 dark:text-gray-400">Grup Terdeteksi dari Webhook:</span>
              <div class="mt-1 flex flex-wrap gap-1.5">
                @foreach ($telegramKnownGroups as $id => $grp)
                  <button type="button" wire:click="selectKnownGroup('{{ $id }}')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-md border transition {{ $telegramTargetGroup == $id ? 'bg-sky-500 text-white border-sky-600 font-semibold' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50' }}">
                    <span>👥 {{ $grp['title'] ?? 'Group' }}</span>
                    <span class="text-[10px] opacity-80 font-mono">({{ $id }})</span>
                  </button>
                @endforeach
              </div>
            </div>
          @endif

          <!-- Quick Tip Guide -->
          <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs flex items-start gap-2">
            <svg class="w-4 h-4 shrink-0 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-[11px] leading-relaxed">
              <b>Cara Mendapatkan Chat ID Grup "CG Bank transfer Request":</b>
              <ol class="list-decimal ml-4 mt-0.5 space-y-0.5">
                <li>Buka grup <b>CG Bank transfer Request</b> di Telegram (di mana CetakiaBot sudah menjadi anggota).</li>
                <li>Ketik perintah <code>/id</code> di grup tersebut.</li>
                <li>Bot CetakiaBot akan membalas dengan <b>Group Chat ID</b> (angka berawalan minus, contoh: <code>-100xxxxxxxxxx</code>) dan sistem otomatis mencatatnya.</li>
                <li>Salin ID tersebut dan tempelkan pada kolom di atas jika belum terisi otomatis.</li>
              </ol>
            </div>
          </div>
        </div>

        <!-- Custom Test Message Note (Optional) -->
        <div>
          <x-label for="telegramCustomMessage" value="Catatan / Pesan Pengujian (Opsional)" class="text-xs font-semibold" />
          <textarea id="telegramCustomMessage" wire:model="telegramCustomMessage" rows="2"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
            placeholder="Ketik catatan khusus untuk pesan pengujian (opsional, sudah ada pesan template standar)..."></textarea>
        </div>

        <!-- Webhook Info Display Panel (Conditional) -->
        @if ($telegramWebhookInfo)
          <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full {{ ($telegramWebhookInfo['ok'] ?? false) ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                Status Endpoint Webhook Telegram API
              </h4>
              <span class="text-[11px] text-gray-400 font-mono">{{ $telegramWebhookInfo['checked_at'] ?? '' }}</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
              <div class="p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400 block text-[10px]">Webhook URL</span>
                <span class="font-mono font-medium text-sky-600 dark:text-sky-400 break-all">{{ $telegramWebhookInfo['data']['url'] ?? '-' }}</span>
              </div>
              <div class="p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400 block text-[10px]">Pending Updates</span>
                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $telegramWebhookInfo['data']['pending_update_count'] ?? 0 }} update</span>
              </div>
              @if (!empty($telegramWebhookInfo['data']['last_error_message']))
                <div class="col-span-full p-2 bg-red-50 dark:bg-red-950/40 rounded border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-xs">
                  <span class="font-bold block text-[10px]">Pesan Error Terakhir Webhook:</span>
                  <span>{{ $telegramWebhookInfo['data']['last_error_message'] }}</span>
                  @if (!empty($telegramWebhookInfo['data']['last_error_date']))
                    <span class="block text-[10px] text-red-500 mt-0.5">Waktu: {{ date('d M Y, H:i:s', $telegramWebhookInfo['data']['last_error_date']) }}</span>
                  @endif
                </div>
              @endif
            </div>
          </div>
        @endif

        <!-- TEST RESULT INSPECTOR (REQUEST & RESPONSE) -->
        @if ($telegramTestResult)
          <div class="space-y-3 pt-2">
            <!-- Overall Status Header -->
            <div class="p-4 rounded-xl border {{ $telegramTestResult['success'] ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800' : 'bg-red-50 border-red-200 dark:bg-red-950/40 dark:border-red-800' }}">
              <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div class="p-2 rounded-full {{ $telegramTestResult['success'] ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white' }}">
                    @if ($telegramTestResult['success'])
                      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                      </svg>
                    @else
                      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    @endif
                  </div>
                  <div>
                    <h4 class="text-sm font-bold {{ $telegramTestResult['success'] ? 'text-emerald-900 dark:text-emerald-200' : 'text-red-900 dark:text-red-200' }}">
                      {{ $telegramTestResult['success'] ? 'BERHASIL! Pesan Uji Coba Terkirim ke Grup Telegram' : 'GAGAL! Pesan Tidak Dapat Terkirim ke Telegram' }}
                    </h4>
                    <p class="text-xs {{ $telegramTestResult['success'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }} mt-0.5">
                      Target: <b>{{ $telegramTestResult['details']['chat_title'] ?? 'CG Bank transfer Request' }}</b> (<code>{{ $telegramTestResult['target_chat_id'] }}</code>) &bull; HTTP {{ $telegramTestResult['status_code'] }} &bull; {{ $telegramTestResult['latency_ms'] }} ms
                    </p>
                  </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold font-mono {{ $telegramTestResult['success'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                  {{ $telegramTestResult['status_code'] }} {{ $telegramTestResult['success'] ? 'OK' : 'FAILED' }}
                </span>
              </div>

              @if (!$telegramTestResult['success'] && !empty($telegramTestResult['details']['description']))
                <div class="mt-3 p-2.5 rounded-lg bg-red-100/70 dark:bg-red-900/50 text-red-800 dark:text-red-200 text-xs">
                  <b>Deskripsi Error dari Telegram:</b>
                  <p class="font-mono mt-0.5">{{ $telegramTestResult['details']['description'] }}</p>
                  <p class="mt-1 text-[11px] text-red-600 dark:text-red-300">
                    💡 <b>Solusi:</b> Pastikan bot CetakiaBot sudah dimasukkan ke dalam grup tersebut dan memiliki izin untuk mengirim pesan (akses ke pesan).
                  </p>
                </div>
              @endif
            </div>

            <!-- Side-by-side or Tabbed Request & Response Inspector -->
            <div x-data="{ tab: 'response' }" class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900 shadow-sm">
              <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 px-3">
                <div class="flex gap-2">
                  <button type="button" @click="tab = 'response'"
                    class="py-2.5 px-3 text-xs font-bold border-b-2 transition-colors flex items-center gap-1.5"
                    :class="tab === 'response' ? 'border-sky-500 text-sky-600 dark:text-sky-400 bg-white dark:bg-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
                    <span>📥 Response Telegram API</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-mono {{ $telegramTestResult['success'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' }}">
                      {{ $telegramTestResult['status_code'] }}
                    </span>
                  </button>
                  <button type="button" @click="tab = 'request'"
                    class="py-2.5 px-3 text-xs font-bold border-b-2 transition-colors flex items-center gap-1.5"
                    :class="tab === 'request' ? 'border-sky-500 text-sky-600 dark:text-sky-400 bg-white dark:bg-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
                    <span>📤 Request Payload</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-mono bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                      POST
                    </span>
                  </button>
                </div>
                <span class="text-[11px] text-gray-400 font-mono">
                  Latency: {{ $telegramTestResult['latency_ms'] }} ms
                </span>
              </div>

              <!-- Response Tab -->
              <div x-show="tab === 'response'" class="p-3">
                <div class="mb-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                  <span class="font-semibold text-gray-700 dark:text-gray-300">Raw JSON Response:</span>
                  <button type="button" x-data="{ copied: false }"
                    @click="navigator.clipboard.writeText(JSON.stringify({{ json_encode($telegramTestResult['response']['body']) }}, null, 2)); copied = true; setTimeout(() => copied = false, 2000)"
                    class="px-2 py-0.5 text-[11px] text-sky-600 dark:text-sky-400 hover:underline">
                    <span x-show="!copied">📋 Salin Response</span>
                    <span x-show="copied" class="text-emerald-600 dark:text-emerald-400">✓ Tersalin!</span>
                  </button>
                </div>
                <pre class="p-3 rounded-lg bg-gray-950 text-gray-100 font-mono text-[11px] leading-relaxed overflow-x-auto max-h-60 selection:bg-sky-500 selection:text-white"><code>{{ json_encode($telegramTestResult['response']['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
              </div>

              <!-- Request Tab -->
              <div x-show="tab === 'request'" class="p-3" style="display: none;">
                <div class="space-y-2">
                  <div class="flex items-center gap-2 text-xs">
                    <span class="px-2 py-0.5 rounded font-mono font-bold bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200">POST</span>
                    <span class="font-mono text-gray-600 dark:text-gray-400 break-all">{{ $telegramTestResult['request']['endpoint'] }}</span>
                  </div>
                  <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Payload Parameters:</span>
                    <button type="button" x-data="{ copied: false }"
                      @click="navigator.clipboard.writeText(JSON.stringify({{ json_encode($telegramTestResult['request']['payload']) }}, null, 2)); copied = true; setTimeout(() => copied = false, 2000)"
                      class="px-2 py-0.5 text-[11px] text-sky-600 dark:text-sky-400 hover:underline">
                      <span x-show="!copied">📋 Salin Payload</span>
                      <span x-show="copied" class="text-emerald-600 dark:text-emerald-400">✓ Tersalin!</span>
                    </button>
                  </div>
                  <pre class="p-3 rounded-lg bg-gray-950 text-gray-100 font-mono text-[11px] leading-relaxed overflow-x-auto max-h-60 selection:bg-sky-500 selection:text-white"><code>{{ json_encode($telegramTestResult['request']['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
              </div>
            </div>
          </div>
        @endif

        <!-- Syirkah Bot Transfer Integration Section -->
        <div class="mt-6 pt-5 border-t border-gray-200 dark:border-gray-700/80 space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300">
                🤖 Integrasi Bot Transfer Pak Fathi
              </span>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                Endpoint: <code>SYIRKAH_TRANSFER_URL</code>
              </span>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" wire:click="processAllApprovedSyirkahTransfers" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition disabled:opacity-50"
                title="Kirim seluruh antrean pembayaran syirkah berstatus APPROVED secara berurutan">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Proses Antrean Pembayaran</span>
              </button>

              <button type="button" wire:click="testSyirkahTransferApi(10000)" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition disabled:opacity-50"
                title="Kirim pengujian transfer sebesar Rp 10.000 dengan catatan TESTING SAJA (memenuhi batas minimum validasi n8n)">
                <span wire:loading.remove wire:target="testSyirkahTransferApi(10000)">
                  <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                  Test Bot Transfer (Rp 10.000)
                </span>
                <span wire:loading wire:target="testSyirkahTransferApi(10000)" class="inline-flex items-center gap-1">
                  <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                  </svg>
                  Menguji...
                </span>
              </button>

              <button type="button" wire:click="testSyirkahTransferApi(1000)" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 transition"
                title="Kirim pengujian transfer Rp 1.000 (untuk menguji respon penolakan nominal minimum)">
                <span>Test Rp 1.000</span>
              </button>
            </div>
          </div>

          <div class="p-3 bg-gray-50 dark:bg-gray-900/60 rounded-xl border border-gray-200 dark:border-gray-700/70 text-xs space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">Target Webhook URL:</span>
              <span class="font-mono font-medium text-emerald-600 dark:text-emerald-400">{{ config('services.syirkah.transfer_url', env('SYIRKAH_TRANSFER_URL', '-')) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">API Secret Key (X-Syirkah-Kunci):</span>
              <span class="font-mono text-gray-700 dark:text-gray-300">
                @php
                  $sec = config('services.syirkah.transfer_secret', env('API_KEY', env('SYIRKAH_TRANSFER_SECRET', '')));
                  $maskedSec = strlen($sec) > 8 ? substr($sec, 0, 4) . '...' . substr($sec, -4) : ($sec ? 'Tersedia' : 'Belum Diatur');
                @endphp
                {{ $maskedSec }}
              </span>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
              * Mode testing otomatis mengirimkan nominal tepat <b>Rp 1.000</b> dengan catatan <code>TESTING SAJA</code> dan prefix <code>UJI-</code> sesuai spesifikasi integrasi.
            </p>
          </div>

          <!-- Syirkah Transfer Test Result Inspector -->
          @if ($syirkahTransferTestResult)
            <div class="p-4 rounded-xl border {{ $syirkahTransferTestResult['success'] ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-800 dark:bg-emerald-950/20' : 'border-amber-200 bg-amber-50/50 dark:border-amber-800 dark:bg-amber-950/20' }} space-y-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $syirkahTransferTestResult['success'] ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white' }}">
                    Status: {{ $syirkahTransferTestResult['status'] }}
                  </span>
                  <span class="text-xs font-mono text-gray-600 dark:text-gray-300">HTTP {{ $syirkahTransferTestResult['http_status'] }}</span>
                </div>
                <span class="font-mono text-xs font-bold text-gray-700 dark:text-gray-300">{{ $syirkahTransferTestResult['id_pengajuan'] }}</span>
              </div>

              @if ($syirkahTransferTestResult['error'])
                <p class="text-xs text-amber-700 dark:text-amber-300">Keterangan: {{ $syirkahTransferTestResult['error'] }}</p>
              @endif

              <div x-data="{ tab: 'response' }" class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900 shadow-sm text-xs">
                <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 px-3">
                  <button type="button" @click="tab = 'response'"
                    class="py-2 px-3 text-xs font-bold border-b-2"
                    :class="tab === 'response' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-white dark:bg-gray-900' : 'border-transparent text-gray-500'">
                    Response Bot Transfer
                  </button>
                  <button type="button" @click="tab = 'payload'"
                    class="py-2 px-3 text-xs font-bold border-b-2"
                    :class="tab === 'payload' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-white dark:bg-gray-900' : 'border-transparent text-gray-500'">
                    Payload Request
                  </button>
                </div>
                <div x-show="tab === 'response'" class="p-3">
                  <pre class="p-2.5 rounded-lg bg-gray-950 text-gray-100 font-mono text-[11px] overflow-x-auto max-h-48"><code>{{ json_encode($syirkahTransferTestResult['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                </div>
                <div x-show="tab === 'payload'" class="p-3" style="display: none;">
                  <pre class="p-2.5 rounded-lg bg-gray-950 text-gray-100 font-mono text-[11px] overflow-x-auto max-h-48"><code>{{ json_encode($syirkahTransferTestResult['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                </div>
              </div>
            </div>
          @endif
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <div class="flex items-center justify-between w-full gap-2">
        <div class="text-[11px] text-gray-500 dark:text-gray-400">
          Target: <b>CG Bank transfer Request</b>
        </div>
        <div class="flex items-center gap-2">
          <x-secondary-button wire:click="closeTelegramTester" wire:loading.attr="disabled">
            {{ __('Tutup') }}
          </x-secondary-button>

          <button type="button" wire:click="sendTelegramGroupTest" wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white shadow-sm transition disabled:opacity-50">
            <span wire:loading.remove wire:target="sendTelegramGroupTest">
              <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
              Kirim Pesan Uji Coba ke Grup
            </span>
            <span wire:loading wire:target="sendTelegramGroupTest" class="inline-flex items-center gap-1.5">
              <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              Mengirim ke Grup Telegram...
            </span>
          </button>
        </div>
      </div>
    </x-slot>
  </x-dialog-modal>
</div>
