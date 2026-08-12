<div x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  <div class="mb-4">
    <div class="flex w-full flex-1 items-center gap-2">
      <div class="relative w-full">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
          <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <!-- Dummy inputs to trap browser aggressive credential autofill -->
        <div class="absolute h-0 w-0 overflow-hidden opacity-0 pointer-events-none -z-50">
          <input type="text" name="dummy_username" autocomplete="username">
          <input type="password" name="dummy_password" autocomplete="current-password">
        </div>
        <x-input type="text" class="block w-full pl-10 pr-10" name="employee_search" id="employee_search" autocomplete="off" wire:model.live.debounce.300ms="search"
          placeholder="{{ __('Search') }}" />
        @if ($search)
          <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        @endif
      </div>
    </div>
  </div>

  <x-admin.employee-summary-cards
    :activeSuspendCount="$activeSuspendCount"
    :suspendCount="$suspendCount"
    :resignCount="$resignCount"
    :firedCount="$firedCount"
  />


  <x-filter-sidebar maxWidth="sm">
    <x-slot name="title">Karyawan Filters</x-slot>
    <x-slot name="actions">
      <button type="button" wire:click="$set('status', ''); $set('division', ''); $set('jobTitle', ''); $set('type', ''); $set('education', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
      </button>
    </x-slot>
    
    <x-slot name="content">
      <div class="flex flex-col gap-6">
        <div>
          <x-label for="status_filter" value="Pilih Status Karyawan" class="mb-1"></x-label>
          <x-select id="status_filter" class="w-full" wire:model.live="status">
            <option value="">Semua Karyawan Aktif</option>
            <option value="active">Aktif</option>
            <option value="suspend">Diskors (Suspend)</option>
            <option value="inactive">Tidak Aktif</option>
            <option value="resign">Mengundurkan Diri (Resign)</option>
            <option value="fired">Dipecat (Fired)</option>
          </x-select>
        </div>
        @if (Auth::user()->isSuperadmin)
        <div>
          <x-label for="division_filter" value="Pilih Divisi" class="mb-1"></x-label>
          <x-select id="division_filter" class="w-full" wire:model.live="division">
            <option value="">{{ __('Select Division') }}</option>
            @foreach (App\Models\Division::all() as $_division)
              <option value="{{ $_division->id }}" {{ $_division->id == $division ? 'selected' : '' }}>
                {{ $_division->name }}
              </option>
            @endforeach
          </x-select>
        </div>
        @endif
        <div>
          <x-label for="jobTitle_filter" value="Pilih Jabatan" class="mb-1"></x-label>
          <x-select id="jobTitle_filter" class="w-full" wire:model.live="jobTitle">
            <option value="">{{ __('Select Job Title') }}</option>
            @foreach (App\Models\JobTitle::all() as $_jobTitle)
              <option value="{{ $_jobTitle->id }}" {{ $_jobTitle->id == $jobTitle ? 'selected' : '' }}>
                {{ $_jobTitle->name }}
              </option>
            @endforeach
          </x-select>
        </div>
        <div>
          <x-label for="type_filter" value="Pilih Tipe Karyawan" class="mb-1"></x-label>
          <x-select id="type_filter" class="w-full" wire:model.live="type">
            <option value="">Semua Tipe Karyawan</option>
            <option value="full-time">Full-time</option>
            <option value="contract">Kontrak (Contract)</option>
            <option value="part-time">Part-time (PT)</option>
            <option value="freelance">Freelance (FR)</option>
            <option value="probation">Probation (PRB)</option>
            <option value="intern">Internship (INT)</option>
            <option value="pkl">PKL (Praktik Kerja Lapangan)</option>
            <option value="outsourcing">Outsourcing</option>
            <option value="volunteer">Volunteer</option>
          </x-select>
        </div>
        <div>
          <x-label for="education_filter" value="Pendidikan Terakhir" class="mb-1"></x-label>
          <x-select id="education_filter" class="w-full" wire:model.live="education">
            <option value="">{{ __('Last Education') }}</option>
            @foreach (App\Models\Education::all() as $_education)
              <option value="{{ $_education->id }}" {{ $_education->id == $education ? 'selected' : '' }}>
                {{ $_education->name }}
              </option>
            @endforeach
          </x-select>
        </div>
      </div>
    </x-slot>
  </x-filter-sidebar>
  <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th scope="col"
            class="relative px-2 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300">
            No.
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Name') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('NIP') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Email') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Phone Number') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            Status
          </th>
          <th scope="col"
            class="hidden px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 sm:table-cell">
            {{ __('City') }}
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
              {{ $user->nip }}
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              {{ $user->email }}
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              {{ $user->phone }}
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium" {{ $wireClick }}>
              @if($user->status === 'active')
                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">Aktif</span>
              @elseif($user->status === 'suspend')
                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900 dark:text-amber-200">Diskors</span>
              @elseif($user->status === 'inactive')
                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-800 dark:bg-gray-700 dark:text-gray-300">Tidak Aktif</span>
              @elseif($user->status === 'resign')
                <span class="inline-flex rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-semibold text-purple-800 dark:bg-purple-900 dark:text-purple-200">Resign</span>
              @elseif($user->status === 'fired')
                <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900 dark:text-red-200">Dipecat</span>
              @endif
            </td>
            <td
              class="{{ $class }} hidden px-6 py-4 text-sm font-medium text-gray-900 dark:text-white sm:table-cell"
              {{ $wireClick }}>
              {{ $user->city }}
            </td>
            <td class="relative flex justify-end gap-2 px-4 py-4">
              <button type="button" wire:click="edit('{{ $user->id }}')" title="Edit Karyawan"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-sky-500 px-2 py-1.5 text-white shadow-sm hover:bg-sky-600 focus:outline-none transition-colors duration-150">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button type="button" wire:click="confirmDeletion('{{ $user->id }}', '{{ $user->name }}')" title="Hapus Karyawan"
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
  @if ($users->isEmpty())
    <div class="my-2 text-center text-sm font-medium text-gray-900 dark:text-gray-100">
      Tidak ada data
    </div>
  @endif
  <div class="mt-3">
    {{ $users->links() }}
  </div>

  <x-confirmation-modal wire:model="confirmingDeletion">
    <x-slot name="title">
      Hapus Karyawan
    </x-slot>

    <x-slot name="content">
      Apakah Anda yakin ingin menghapus <b>{{ $deleteName }}</b>?
    </x-slot>

    <x-slot name="footer">
      <x-danger-button wire:click="delete" wire:loading.attr="disabled">
        Ya, Hapus
      </x-danger-button>
      <x-secondary-button class="ms-2" wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>
    </x-slot>
  </x-confirmation-modal>

  <x-dialog-modal wire:model="creating">
    <x-slot name="title">
      Karyawan Baru
    </x-slot>

    <form wire:submit="create" autocomplete="off">
      <x-slot name="content">
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
          <div x-data="{ photoName: null, photoPreview: null }" class="flex flex-col items-center">
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

            <x-label for="photo" value="{{ __('Photo') }}" class="font-bold" />

            <!-- Current Profile Photo -->
            <div class="mt-2 h-20 w-20 rounded-full outline outline-gray-400" x-show="! photoPreview">
            </div>

            <!-- New Profile Photo Preview -->
            <div class="mt-2" x-show="photoPreview" style="display: none;">
              <span class="block h-20 w-20 rounded-full bg-cover bg-center bg-no-repeat"
                x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
              </span>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-2">
              <x-secondary-button class="mt-2" type="button" x-on:click.prevent="$refs.photo.click()">
                {{ __('Select A New Photo') }}
              </x-secondary-button>

              @if ($form->user?->profile_photo_path)
                <x-secondary-button type="button" class="mt-2" wire:click="deleteProfilePhoto">
                  {{ __('Remove Photo') }}
                </x-secondary-button>
              @endif
            </div>

            @error('form.photo')
              <x-input-error for="form.photo" message="{{ $message }}" class="mt-2 text-center" />
            @enderror
          </div>
        @endif
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full sm:w-1/3">
            <x-label for="name">Nama Karyawan</x-label>
            <x-input id="name" class="mt-1 block w-full" type="text" wire:model="form.name" required />
            @error('form.name')
              <x-input-error for="form.name" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full sm:w-1/3">
            <x-label for="type">Tipe Karyawan</x-label>
            <x-select id="type" class="mt-1 block w-full" wire:model.live="form.type">
              <option value="full-time">Full-time</option>
              <option value="contract">Kontrak (Contract)</option>
              <option value="part-time">Part-time (PT)</option>
              <option value="freelance">Freelance (FR)</option>
              <option value="probation">Probation (PRB)</option>
              <option value="intern">Internship (INT)</option>
              <option value="pkl">PKL (Praktik Kerja Lapangan)</option>
              <option value="outsourcing">Outsourcing</option>
              <option value="volunteer">Volunteer</option>
            </x-select>
            @error('form.type')
              <x-input-error for="form.type" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full sm:w-1/3">
            <div class="flex items-center justify-between">
              <x-label for="nip">NIP (Auto / Manual)</x-label>
              <button type="button" wire:click="regenerateNip" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                Generate
              </button>
            </div>
            <x-input id="nip" class="mt-1 block w-full" type="text" wire:model.live="form.nip"
              placeholder="Contoh: 26080001" required />
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
          <div class="w-full" x-data="{ show: false }">
            <x-label for="password">{{ __('Password') }}</x-label>
            <div class="relative mt-1 w-full">
              <x-input id="password" class="block w-full pr-10" x-bind:type="show ? 'text' : 'password'" wire:model="form.password"
                placeholder="New Password" />
              <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg x-cloak x-show="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
            <p class="mt-1 text-sm dark:text-gray-400">Default password: <b>password</b></p>
            @error('form.password')
              <x-input-error for="form.password" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="gender">{{ __('Gender') }}</x-label>
            <div class="my-3 flex flex-row gap-5">
              <div class="flex items-center">
                <input type="radio" id="gender-male" wire:model="form.gender" value="male" />
                <x-label for="gender-male" class="ml-2">{{ __('Male') }}</x-label>
              </div>
              <div class="flex items-center">
                <input type="radio" id="gender-female" wire:model="form.gender" value="female" />
                <x-label for="gender-female" class="ml-2">{{ __('Female') }}</x-label>
              </div>
            </div>
            @error('form.gender')
              <x-input-error for="form.gender" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="status">Status</x-label>
            <select id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600" wire:model="form.status">
                <option value="active">Aktif</option>
                <option value="inactive">Tidak Aktif</option>
                <option value="resign">Mengundurkan Diri (resign)</option>
                <option value="suspend">Diskors (suspend)</option>
                <option value="fired">Dipecat (fired)</option>
            </select>
            @error('form.status')
              <x-input-error for="form.status" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="phone">{{ __('Phone') }}</x-label>
            <x-input id="phone" class="mt-1 block w-full" type="number" wire:model="form.phone"
              placeholder="+628123456789" />
            @error('form.phone')
              <x-input-error for="form.phone" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="birth_date">{{ __('Birth Date') }}</x-label>
            <x-input id="birth_date" class="mt-1 block w-full" type="date" wire:model="form.birth_date" />
            @error('form.birth_date')
              <x-input-error for="form.birth_date" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="birth_place">{{ __('Birth Place') }}</x-label>
            <x-input id="birth_place" class="mt-1 block w-full" type="text" wire:model="form.birth_place"
              placeholder="Jakarta" />
            @error('form.birth_place')
              <x-input-error for="form.birth_place" class="mt-2" message="{{ $message }}" />
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
        <div class="mt-4">
          <x-label for="address">{{ __('Address') }}</x-label>
          <x-input id="address" class="mt-1 block w-full" type="text" wire:model="form.address"
            placeholder="Jl. Jend. Sudirman" />
          @error('form.address')
            <x-input-error for="form.address" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
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
          <div class="w-full">
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
          <div class="w-full">
            <x-label for="form.education_id" value="{{ __('Last Education') }}" />
            <x-select id="form.education_id" class="mt-1 block w-full" wire:model="form.education_id">
              <option value="">{{ __('Select Education') }}</option>
              @foreach (App\Models\Education::all() as $education)
                <option value="{{ $education->id }}" {{ $education->id == $form->education_id ? 'selected' : '' }}>
                  {{ $education->name }}
                </option>
              @endforeach
            </x-select>
            @error('form.education_id')
              <x-input-error for="form.education_id" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        @if (Auth::user()->isSuperadmin)
        <div class="mt-4">
          <label for="create_count_wfo" class="flex items-center">
            <x-checkbox id="create_count_wfo" wire:model="form.count_wfo" />
            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Count WFO (Centang jika perhitungan WFH = WFO)</span>
          </label>
        </div>
        @endif
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

  <x-dialog-modal wire:model="editing">
    <x-slot name="title">
      Edit Karyawan
    </x-slot>

    <form wire:submit.prevent="update" id="user-edit" autocomplete="off">
      <x-slot name="content">
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
          <div x-data="{ photoName: null, photoPreview: null }" class="flex flex-col items-center">
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

            <x-label for="photo" value="{{ __('Photo') }}" class="font-bold" />

            <!-- Current Profile Photo -->
            <div class="mt-2" x-show="! photoPreview">
              <img src="{{ $form->user?->profile_photo_url }}" alt="{{ $form->user?->name }}"
                class="h-20 w-20 rounded-full object-cover">
            </div>

            <!-- New Profile Photo Preview -->
            <div class="mt-2" x-show="photoPreview" style="display: none;">
              <span class="block h-20 w-20 rounded-full bg-cover bg-center bg-no-repeat"
                x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
              </span>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-2">
              <x-secondary-button class="mt-2" type="button" x-on:click.prevent="$refs.photo.click()">
                {{ __('Select A New Photo') }}
              </x-secondary-button>

              @if ($form->user?->profile_photo_path)
                <x-secondary-button type="button" class="mt-2" wire:click="deleteProfilePhoto">
                  {{ __('Remove Photo') }}
                </x-secondary-button>
              @endif
            </div>

            @error('form.photo')
              <x-input-error for="form.photo" message="{{ $message }}" class="mt-2 text-center" />
            @enderror
          </div>
        @endif
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full sm:w-1/3">
            <x-label for="name">Nama Karyawan</x-label>
            <x-input id="name" class="mt-1 block w-full" type="text" wire:model="form.name" />
            @error('form.name')
              <x-input-error for="form.name" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full sm:w-1/3">
            <x-label for="type_edit">Tipe Karyawan</x-label>
            <x-select id="type_edit" class="mt-1 block w-full" wire:model.live="form.type">
              <option value="full-time">Full-time</option>
              <option value="contract">Kontrak (Contract)</option>
              <option value="part-time">Part-time (PT)</option>
              <option value="freelance">Freelance (FR)</option>
              <option value="probation">Probation (PRB)</option>
              <option value="intern">Internship (INT)</option>
              <option value="pkl">PKL (Praktik Kerja Lapangan)</option>
              <option value="outsourcing">Outsourcing</option>
              <option value="volunteer">Volunteer</option>
            </x-select>
            @error('form.type')
              <x-input-error for="form.type" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full sm:w-1/3">
            <div class="flex items-center justify-between">
              <x-label for="nip_edit">NIP</x-label>
              <button type="button" wire:click="regenerateNip" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                Auto Generate
              </button>
            </div>
            <x-input id="nip_edit" class="mt-1 block w-full" type="text" wire:model.live="form.nip"
              placeholder="Contoh: 26080001" required />
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
          <div class="w-full" x-data="{ show: false }">
            <x-label for="password">{{ __('Password') }}</x-label>
            <div class="relative mt-1 w-full">
              <x-input id="password" class="block w-full pr-10" x-bind:type="show ? 'text' : 'password'" wire:model="form.password"
                placeholder="New Password" />
              <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg x-cloak x-show="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
            @error('form.password')
              <x-input-error for="form.password" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="gender">{{ __('Gender') }}</x-label>
            <div class="my-3 flex flex-row gap-5">
              <div class="flex items-center">
                <input type="radio" id="gender-male" wire:model="form.gender" value="male" />
                <x-label for="gender-male" class="ml-2">{{ __('Male') }}</x-label>
              </div>
              <div class="flex items-center">
                <input type="radio" id="gender-female" wire:model="form.gender" value="female" />
                <x-label for="gender-female" class="ml-2">{{ __('Female') }}</x-label>
              </div>
            </div>
            @error('form.gender')
              <x-input-error for="form.gender" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="status">Status</x-label>
            <select id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600" wire:model="form.status">
                <option value="active">Aktif</option>
                <option value="inactive">Tidak Aktif</option>
                <option value="resign">Mengundurkan Diri (resign)</option>
                <option value="suspend">Diskors (suspend)</option>
                <option value="fired">Dipecat (fired)</option>
            </select>
            @error('form.status')
              <x-input-error for="form.status" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="phone">{{ __('Phone') }}</x-label>
            <x-input id="phone" class="mt-1 block w-full" type="text" wire:model="form.phone"
              placeholder="+628123456789" />
            @error('form.phone')
              <x-input-error for="form.phone" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="birth_date">{{ __('Birth Date') }}</x-label>
            <x-input id="birth_date" class="mt-1 block w-full" type="date" wire:model="form.birth_date" />
            @error('form.birth_date')
              <x-input-error for="form.birth_date" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="birth_place">{{ __('Birth Place') }}</x-label>
            <x-input id="birth_place" class="mt-1 block w-full" type="text" wire:model="form.birth_place"
              placeholder="Jakarta" />
            @error('form.birth_place')
              <x-input-error for="form.birth_place" class="mt-2" message="{{ $message }}" />
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
        <div class="mt-4">
          <x-label for="address">{{ __('Address') }}</x-label>
          <x-input id="address" class="mt-1 block w-full" type="text" wire:model="form.address"
            placeholder="Jl. Jend. Sudirman" />
          @error('form.address')
            <x-input-error for="form.address" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
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
          <div class="w-full">
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
          <div class="w-full">
            <x-label for="form.education_id" value="{{ __('Last Education') }}" />
            <x-select id="form.education_id" class="mt-1 block w-full" wire:model="form.education_id">
              <option value="">{{ __('Select Education') }}</option>
              @foreach (App\Models\Education::all() as $education)
                <option value="{{ $education->id }}" {{ $education->id == $form->education_id ? 'selected' : '' }}>
                  {{ $education->name }}
                </option>
              @endforeach
            </x-select>
            @error('form.education_id')
              <x-input-error for="form.education_id" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        @if (Auth::user()->isSuperadmin)
        <div class="mt-4">
          <label for="edit_count_wfo" class="flex items-center">
            <x-checkbox id="edit_count_wfo" wire:model="form.count_wfo" />
            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Count WFO (Centang jika perhitungan WFH = WFO)</span>
          </label>
        </div>
        @endif
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

  <x-modal wire:model="showDetail">
    @if ($form->user)
      @php
        $division = $form->user->division?->name ?? '-';
        $jobTitle = $form->user->jobTitle?->name ?? '-';
        $education = $form->user->education?->name ?? '-';
      @endphp
      <div class="flex flex-col min-h-0 max-h-[82vh] sm:max-h-[88vh] overflow-hidden">
        <!-- Fixed Header -->
        <div class="px-6 pt-5 pb-3.5 shrink-0 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-white/90 dark:bg-gray-900/90 backdrop-blur-md">
          <div>
            <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">
              Detail Karyawan: {{ $form->user->name }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              NIP: {{ $form->user->nip }}
            </p>
          </div>
          <button type="button" wire:click="$set('showDetail', false)" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300 transition-colors">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Scrollable Vertical Body Container -->
        <div class="px-6 py-4 overflow-y-auto min-h-0 flex-1 space-y-4 custom-scrollbar-y">
          <div class="my-2 flex items-center justify-center">
            <img class="h-28 w-28 rounded-full object-cover shadow-md border-2 border-sky-400/50" src="{{ $form->user->profile_photo_url }}"
              alt="{{ $form->user->name }}" />
          </div>

          <div class="text-center text-lg font-bold text-gray-900 dark:text-gray-100">
            {{ $form->user->name }}
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400 pt-2">
            <div>
              <x-label value="NIP" />
              <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $form->user->nip }}</p>
            </div>
            <div>
              <x-label value="{{ __('Email') }}" />
              <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $form->user->email }}</p>
            </div>
            <div>
              <x-label value="{{ __('Phone') }}" />
              <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $form->user->phone }}</p>
            </div>
            <div>
              <x-label value="{{ __('Gender') }}" />
              <p class="font-semibold text-gray-800 dark:text-gray-200">{{ __($form->user->gender) }}</p>
            </div>
            <div>
              <x-label value="{{ __('Birth Date') }}" />
              <p class="font-semibold text-gray-800 dark:text-gray-200">
                @if ($form->user->birth_date)
                  {{ \Illuminate\Support\Carbon::parse($form->user->birth_date)->format('D d M Y') }}
                @else
                  -
                @endif
              </p>
            </div>
            <div>
              <x-label value="{{ __('Birth Place') }}" />
              <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $form->user->birth_place ?? '-' }}</p>
            </div>
            <div>
              <x-label value="{{ __('City') }}" />
              <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $form->user->city ?? '-' }}</p>
            </div>
            <div>
              <x-label value="{{ __('Job Title') }}" />
              <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $jobTitle }}</p>
            </div>
            <div>
              <x-label value="{{ __('Division') }}" />
              <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $division }}</p>
            </div>
            <div>
              <x-label value="{{ __('Last Education') }}" />
              <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $education }}</p>
            </div>
            <div class="sm:col-span-2">
              <x-label value="{{ __('Address') }}" />
              <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $form->user->address ?? '-' }}</p>
            </div>
          </div>
        </div>

        <!-- Fixed Footer -->
        <div class="flex flex-row justify-end bg-gray-50 px-6 py-3.5 text-end dark:bg-gray-800/80 shrink-0 border-t border-gray-200 dark:border-gray-700">
          <x-secondary-button wire:click="$set('showDetail', false)">
            Tutup
          </x-secondary-button>
        </div>
      </div>
    @endif
  </x-modal>
</div>
