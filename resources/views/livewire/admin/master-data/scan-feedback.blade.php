<div>
  <div class="py-0">
    <div class="w-full">
      <div class="overflow-hidden bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 sm:rounded-2xl p-6">

        <!-- Header Section inside Livewire Root DOM Boundary -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-5 dark:border-gray-700">
          <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
              Master Data Scan Feedback (Ucapan & Motivasi Absen)
            </h2>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Kelola daftar kata-kata ucapan motivasi, apresiasi, dan teguran ramah saat karyawan melakukan scan presensi.
            </p>
          </div>
          <div>
            <x-button type="button" wire:click="create" class="!py-2.5 !px-4 bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="mr-2 h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
              Tambah Ucapan Baru
            </x-button>
          </div>
        </div>

        <!-- Filters & Search -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div>
            <x-label for="categoryFilter" value="Filter Kategori" class="mb-1" />
            <x-select id="categoryFilter" class="w-full" wire:model.live="categoryFilter">
              <option value="">Semua Kategori</option>
              <option value="super_early">Super Early (> 30 mnt awal)</option>
              <option value="early">Early (15-30 mnt awal)</option>
              <option value="on_time">On Time (Tepat Waktu)</option>
              <option value="late_mild">Late Mild (Telat 1-15 mnt)</option>
              <option value="late_severe">Late Severe (Telat > 15 mnt)</option>
              <option value="out">Check-out (Pulang Kerja)</option>
            </x-select>
          </div>

          <div class="sm:col-span-2">
            <x-label for="search" value="Cari Ucapan / Judul" class="mb-1" />
            <x-input id="search" type="text" class="w-full" wire:model.live.debounce.300ms="search" placeholder="Cari berdasarkan judul atau isi ucapan..." />
          </div>
        </div>

        <!-- Feedback Table -->
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
          <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                  Kategori
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                  Judul Pop-up
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                  Isi Ucapan Motivasi / Teguran
                </th>
                <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                  Status
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
              @forelse ($feedbacks as $fb)
                @php
                  $catBadge = match ($fb->category) {
                      'super_early' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300',
                      'early' => 'bg-green-100 text-green-800 dark:bg-green-900/60 dark:text-green-300',
                      'on_time' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300',
                      'late_mild' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300',
                      'late_severe' => 'bg-red-100 text-red-800 dark:bg-red-900/60 dark:text-red-300',
                      'out' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300',
                      default => 'bg-gray-100 text-gray-800',
                  };
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                  <td class="whitespace-nowrap px-4 py-4 text-sm font-medium">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $catBadge }}">
                      {{ strtoupper(str_replace('_', ' ', $fb->category)) }}
                    </span>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">
                    {{ $fb->title }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                    "{{ $fb->message }}"
                  </td>
                  <td class="whitespace-nowrap px-4 py-4 text-center text-sm">
                    <button type="button" wire:click="toggleActive('{{ $fb->id }}')" class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold transition-all {{ $fb->is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-950 dark:text-rose-300' }}">
                      {{ $fb->is_active ? '● Aktif' : '○ Non-Aktif' }}
                    </button>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-center text-sm font-medium">
                    <div class="flex items-center justify-center gap-2">
                      <x-secondary-button type="button" wire:click="edit('{{ $fb->id }}')" class="!py-1 !px-2 text-xs">
                        Edit
                      </x-secondary-button>
                      <x-danger-button type="button" wire:click="confirmDelete('{{ $fb->id }}')" class="!py-1 !px-2 text-xs">
                        Hapus
                      </x-danger-button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Belum ada data scan feedback ucapan. Klik <b>Tambah Ucapan Baru</b> untuk menambahkan.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-4">
          {{ $feedbacks->links() }}
        </div>

      </div>
    </div>
  </div>

  <!-- Modal Create -->
  <x-dialog-modal wire:model.live="showCreateModal">
    <x-slot name="title">
      Tambah Ucapan Scan Feedback Baru
    </x-slot>

    <x-slot name="content">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <x-label for="category" value="Kategori Keterlambatan / Kehadiran" class="mb-1" />
          <x-select id="category" class="w-full" wire:model="category">
            <option value="super_early">Super Early (> 30 mnt awal)</option>
            <option value="early">Early (15-30 mnt awal)</option>
            <option value="on_time">On Time (Tepat Waktu)</option>
            <option value="late_mild">Late Mild (Telat 1-15 mnt)</option>
            <option value="late_severe">Late Severe (Telat > 15 mnt)</option>
            <option value="out">Check-out (Pulang Kerja)</option>
          </x-select>
          <x-input-error for="category" class="mt-1" />
        </div>

        <div>
          <x-label for="title" value="Judul Pop-up" class="mb-1" />
          <x-input id="title" type="text" class="w-full" wire:model="title" placeholder="Contoh: Luar Biasa!, Hebat!" />
          <x-input-error for="title" class="mt-1" />
        </div>

        <div class="sm:col-span-2">
          <x-label for="message" value="Isi Pesan Ucapan (Gunakan {name} untuk menyisipkan nama karyawan)" class="mb-1" />
          <textarea id="message" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" wire:model="message" placeholder="Contoh: Gokill {name}! Kamu awal banget hari ini. Panutan!"></textarea>
          <x-input-error for="message" class="mt-1" />
        </div>

        <div>
          <x-label for="icon" value="Icon / Emoji Code" class="mb-1" />
          <x-input id="icon" type="text" class="w-full" wire:model="icon" placeholder="sparkles, fire, clock, check-circle..." />
          <x-input-error for="icon" class="mt-1" />
        </div>

        <div>
          <x-label for="badge_color" value="Warna Theme Modal" class="mb-1" />
          <x-select id="badge_color" class="w-full" wire:model="badge_color">
            <option value="green">Hijau (Green)</option>
            <option value="blue">Biru (Blue)</option>
            <option value="amber">Kuning (Amber/Warning)</option>
            <option value="red">Merah (Red/Danger)</option>
            <option value="purple">Ungu (Purple)</option>
          </x-select>
          <x-input-error for="badge_color" class="mt-1" />
        </div>

        <div class="flex items-center sm:col-span-2">
          <label class="inline-flex items-center cursor-pointer">
            <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Aktifkan Ucapan Ini</span>
          </label>
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$set('showCreateModal', false)">
        Batal
      </x-secondary-button>
      <x-button class="ml-2 bg-indigo-600 hover:bg-indigo-700" wire:click="store">
        Simpan Ucapan
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Edit -->
  <x-dialog-modal wire:model.live="showEditModal">
    <x-slot name="title">
      Edit Ucapan Scan Feedback
    </x-slot>

    <x-slot name="content">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <x-label for="category_edit" value="Kategori Keterlambatan / Kehadiran" class="mb-1" />
          <x-select id="category_edit" class="w-full" wire:model="category">
            <option value="super_early">Super Early (> 30 mnt awal)</option>
            <option value="early">Early (15-30 mnt awal)</option>
            <option value="on_time">On Time (Tepat Waktu)</option>
            <option value="late_mild">Late Mild (Telat 1-15 mnt)</option>
            <option value="late_severe">Late Severe (Telat > 15 mnt)</option>
            <option value="out">Check-out (Pulang Kerja)</option>
          </x-select>
          <x-input-error for="category" class="mt-1" />
        </div>

        <div>
          <x-label for="title_edit" value="Judul Pop-up" class="mb-1" />
          <x-input id="title_edit" type="text" class="w-full" wire:model="title" />
          <x-input-error for="title" class="mt-1" />
        </div>

        <div class="sm:col-span-2">
          <x-label for="message_edit" value="Isi Pesan Ucapan (Gunakan {name} untuk menyisipkan nama karyawan)" class="mb-1" />
          <textarea id="message_edit" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" wire:model="message"></textarea>
          <x-input-error for="message" class="mt-1" />
        </div>

        <div>
          <x-label for="icon_edit" value="Icon / Emoji Code" class="mb-1" />
          <x-input id="icon_edit" type="text" class="w-full" wire:model="icon" />
          <x-input-error for="icon" class="mt-1" />
        </div>

        <div>
          <x-label for="badge_color_edit" value="Warna Theme Modal" class="mb-1" />
          <x-select id="badge_color_edit" class="w-full" wire:model="badge_color">
            <option value="green">Hijau (Green)</option>
            <option value="blue">Biru (Blue)</option>
            <option value="amber">Kuning (Amber/Warning)</option>
            <option value="red">Merah (Red/Danger)</option>
            <option value="purple">Ungu (Purple)</option>
          </x-select>
          <x-input-error for="badge_color" class="mt-1" />
        </div>

        <div class="flex items-center sm:col-span-2">
          <label class="inline-flex items-center cursor-pointer">
            <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Aktifkan Ucapan Ini</span>
          </label>
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$set('showEditModal', false)">
        Batal
      </x-secondary-button>
      <x-button class="ml-2 bg-indigo-600 hover:bg-indigo-700" wire:click="update">
        Simpan Perubahan
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Delete -->
  <x-confirmation-modal wire:model.live="showDeleteModal">
    <x-slot name="title">
      Hapus Scan Feedback Ucapan
    </x-slot>

    <x-slot name="content">
      Apakah Anda yakin ingin menghapus ucapan scan feedback ini? Tindakan ini tidak dapat dibatalkan.
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$set('showDeleteModal', false)">
        Batal
      </x-secondary-button>
      <x-danger-button class="ml-2" wire:click="delete">
        Hapus Ucapan
      </x-danger-button>
    </x-slot>
  </x-confirmation-modal>
</div>
