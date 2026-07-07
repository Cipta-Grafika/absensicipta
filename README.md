<p align="center">
  <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTqx9hRtjkEr22Lb7D5tjDuCc3rSz6qgA26aAlqZSdr3Q&s=10" alt="Absensi Cipta Grafika Logo" width="120" height="120" />
</p>

<h1 align="center">Absensi Cipta Grafika</h1>

<p align="center">
  Aplikasi web manajemen absensi karyawan berbasis QR Code dan GPS untuk PT Cipta Grafika.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel" alt="Laravel" />
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php" alt="PHP" />
  <img src="https://img.shields.io/badge/Livewire-3-FB70A9?logo=livewire" alt="Livewire" />
  <img src="https://img.shields.io/badge/Jetstream-5-4A5568" alt="Jetstream" />
  <img src="https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql" alt="MySQL" />
  <img src="https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?logo=tailwindcss" alt="Tailwind CSS" />
  <img src="https://img.shields.io/badge/Vite-5-646CFF?logo=vite" alt="Vite" />
  <img src="https://img.shields.io/badge/License-MIT-green" alt="License" />
</p>

---

## ✨ Fitur

- **Absensi QR Code** — Karyawan melakukan scan QR Code unik miliknya untuk check-in & check-out
- **Validasi GPS / Lokasi** — Memverifikasi lokasi karyawan menggunakan Leaflet.js + OpenStreetMap saat absensi
- **Dashboard Admin** — Ringkasan statistik kehadiran, ketidakhadiran, dan izin secara real-time
- **Manajemen Karyawan** — CRUD lengkap data karyawan beserta foto profil
- **Manajemen Absensi** — Rekap absensi harian, laporan bulanan, dan filter per karyawan
- **Pengajuan Izin/Cuti** — Karyawan dapat mengajukan izin langsung dari portal mereka
- **Master Data** — Manajemen Divisi, Jabatan, Pendidikan, dan Shift kerja
- **Manajemen Barcode** — Generate, cetak, dan unduh QR Code per karyawan (Superadmin)
- **Import & Export** — Import/export data karyawan dan absensi via file Excel (`.xlsx`)
- **Laporan PDF** — Cetak laporan absensi dalam format PDF menggunakan DomPDF
- **Autentikasi & Otorisasi** — Sistem login dengan tiga level akses: Karyawan, Admin, Superadmin
- **Two-Factor Authentication (2FA)** — Keamanan login berlapis via Laravel Jetstream
- **Riwayat Absensi** — Karyawan dapat melihat riwayat kehadiran pribadi mereka
- **Manajemen Profil** — Karyawan & admin dapat memperbarui data profil dan foto

---

## 🛠 Tech Stack

| Layer | Teknologi | Versi |
|---|---|---|
| Framework | [Laravel](https://laravel.com) | ^11.9 |
| Bahasa | PHP | ^8.2 |
| Auth & Profile | [Laravel Jetstream](https://jetstream.laravel.com) | ^5.1 |
| API Auth Token | [Laravel Sanctum](https://laravel.com/docs/sanctum) | ^4.0 |
| Reaktif UI | [Livewire](https://livewire.laravel.com) | ^3.0 |
| Database | MySQL / MariaDB | 8+ |
| QR Code | [Endroid QR Code](https://github.com/endroid/qr-code) | ^5.0 |
| Peta & GPS | [Leaflet.js](https://leafletjs.com) + OpenStreetMap | — |
| Export PDF | [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) | ^2.2 |
| Import/Export Excel | [Maatwebsite Excel](https://laravel-excel.com) | ^3.1 |
| Pemrosesan Gambar | [Intervention Image](https://image.intervention.io) | ^3.6 |
| Kalkulasi Jarak | [ballen/distical](https://packagist.org/packages/ballen/distical) | ^3.1 |
| Ikon | [Blade Heroicons](https://github.com/blade-ui-kit/blade-heroicons) | ^2.3 |
| Styling | [Tailwind CSS](https://tailwindcss.com) | ^3.4 |
| Build Tool | [Vite](https://vitejs.dev) + [Laravel Vite Plugin](https://laravel.com/docs/vite) | ^5.0 |
| Testing | [Pest PHP](https://pestphp.com) | ^2.0 |

---

## 🚀 Instalasi & Konfigurasi

### Prasyarat

Pastikan sistem Anda sudah terpasang:

- **PHP** `>= 8.2` dengan ekstensi: `mbstring`, `xml`, `pdo_mysql`, `zip`, `gd`, `fileinfo`
- **Composer** `>= 2.x`
- **Node.js** `>= 18.x` & **npm**
- **MySQL** `>= 8` atau **MariaDB** `>= 10.4`

---

### 1. Clone Repository

```bash
git clone https://github.com/Cipta-Grafika/absensicipta.git
cd absensicipta
```

---

### 2. Install Dependency PHP

```bash
composer install
```

---

### 3. Install Dependency JavaScript

```bash
npm install
```

---

### 4. Konfigurasi Environment

Buat file `.env` dari template contoh:

```bash
cp .env.example .env
```

Kemudian edit file `.env` dan sesuaikan nilai berikut:

```env
# Nama Aplikasi
APP_NAME="Absensi Karyawan"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_absensi_karyawan
DB_USERNAME=root
DB_PASSWORD=your_password
```

---

### 5. Generate Application Key

```bash
php artisan key:generate --ansi
```

---

### 6. Migrasi Database

Jalankan migrasi untuk membuat seluruh tabel yang diperlukan:

```bash
php artisan migrate
```

Tabel yang akan dibuat meliputi: `users`, `attendances`, `barcodes`, `shifts`, `divisions`, `job_titles`, `educations`, `personal_access_tokens`, `sessions`, `cache`, dan `jobs`.

---

### 7. Seed Data Awal

Pilih salah satu opsi berikut sesuai kebutuhan:

```bash
# Opsi A: Seed data dasar saja (admin, superadmin, master data)
php artisan db:seed DatabaseSeeder

# Opsi B: Seed data lengkap + data dummy (karyawan & absensi acak)
php artisan db:seed FakeDataSeeder
```

> **Akun default setelah seeding:**
>
> | Role | Email | Password |
> |---|---|---|
> | Superadmin | `superadmin@example.com` | `password` |
> | Admin | `admin@example.com` | `password` |
> | Karyawan | `karyawan@example.com` | `password` |

---

### 8. Build Aset Frontend

```bash
# Mode development (dengan hot-reload)
npm run dev

# Mode produksi (untuk deployment)
npm run build
```

---

### 9. Jalankan Server

```bash
php artisan serve
```

Buka [http://localhost:8000](http://localhost:8000) di browser Anda.

---

## 📁 Struktur Proyek

```
absensicipta/
├── app/
│   ├── Actions/             # Fortify & Jetstream actions (create team, dll)
│   ├── Exports/             # Class export Excel (karyawan & absensi)
│   ├── Imports/             # Class import Excel (karyawan & absensi)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/       # Controller area admin (Attendance, Barcode, Employee, dll)
│   │   │   ├── HomeController.php
│   │   │   └── UserAttendanceController.php
│   │   └── Middleware/      # Middleware (Admin, User, SuperAdmin)
│   ├── Livewire/
│   │   ├── Admin/
│   │   │   ├── MasterData/  # Komponen Livewire: Divisi, Jabatan, Pendidikan, Shift
│   │   │   ├── ImportExport/# Komponen import/export karyawan & absensi
│   │   │   ├── AttendanceComponent.php
│   │   │   ├── EmployeeComponent.php
│   │   │   ├── BarcodeComponent.php
│   │   │   └── DashboardComponent.php
│   │   ├── ScanComponent.php         # Komponen scan QR Code karyawan
│   │   └── AttendanceHistoryComponent.php
│   ├── Models/              # Eloquent Models: User, Attendance, Barcode, Shift, dll
│   ├── BarcodeGenerator.php # Service pembuat QR Code
│   └── Helpers.php          # Helper global aplikasi
├── database/
│   ├── migrations/          # Semua file migrasi tabel
│   ├── seeders/             # DatabaseSeeder, AdminSeeder, FakeDataSeeder
│   └── factories/           # Factory untuk data dummy
├── resources/
│   ├── css/                 # File CSS kustom
│   ├── js/                  # Entry point JavaScript (app.js)
│   └── views/
│       ├── admin/           # Blade views area admin
│       ├── livewire/        # Blade views komponen Livewire
│       ├── attendances/     # Views absensi karyawan
│       ├── auth/            # Views autentikasi (login, register, 2FA)
│       ├── layouts/         # Layout utama aplikasi
│       └── profile/         # Views manajemen profil
├── routes/
│   ├── web.php              # Definisi semua route web
│   └── api.php              # Route API
├── docs/                    # Dokumentasi tambahan (deployment, MySQL config)
├── .env.example             # Template konfigurasi environment
├── composer.json            # Dependency PHP
├── package.json             # Dependency JavaScript
├── tailwind.config.cjs      # Konfigurasi Tailwind CSS
└── vite.config.js           # Konfigurasi Vite
```

---

## 📦 Perintah yang Tersedia

### NPM Scripts

| Perintah | Deskripsi |
|---|---|
| `npm run dev` | Jalankan Vite dev server (hot-reload) |
| `npm run build` | Build aset untuk produksi |

### Artisan Commands

| Perintah | Deskripsi |
|---|---|
| `php artisan serve` | Jalankan server pengembangan Laravel |
| `php artisan migrate` | Jalankan semua migrasi database |
| `php artisan migrate:fresh --seed` | Reset & migrasi ulang database + seed |
| `php artisan db:seed DatabaseSeeder` | Seed data awal (admin & master data) |
| `php artisan db:seed FakeDataSeeder` | Seed data dummy karyawan & absensi |
| `php artisan key:generate` | Generate application key baru |
| `php artisan storage:link` | Buat symlink storage ke public |
| `php artisan queue:work` | Jalankan queue worker |

---

## 🔐 Level Akses (Role)

| Role | Akses |
|---|---|
| **Superadmin** | Semua fitur termasuk manajemen barcode QR Code & master data global (divisi, jabatan, pendidikan, shift) |
| **Admin** | Dashboard, manajemen karyawan, rekap absensi, laporan, import/export, manajemen admin |
| **Karyawan** | Scan absensi, pengajuan izin/cuti, riwayat absensi, manajemen profil |

---

## 📖 Dokumentasi Tambahan

Dokumentasi lebih lengkap tersedia di folder [`docs/`](./docs/):

- [`LARAVEL_DEPLOYMENT.md`](./docs/LARAVEL_DEPLOYMENT.md) — Panduan deployment ke server produksi
- [`MYSQL_CONFIG.md`](./docs/MYSQL_CONFIG.md) — Konfigurasi MySQL untuk produksi
- [`IMPORT_SQL.md`](./docs/IMPORT_SQL.md) — Panduan import database via SQL
- [`SETTING_ULANG_MYSQL.md`](./docs/SETTING_ULANG_MYSQL.md) — Troubleshooting & reset konfigurasi MySQL

---

## 🧪 Testing

Proyek ini menggunakan [Pest PHP](https://pestphp.com) sebagai framework testing.

```bash
php artisan test
```

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah **MIT License**.

---

<p align="center">
  Dibuat dengan 🔥 oleh <a href="https://github.com/astrocoding">Zaenal Alfian</a> untuk <strong>Cipta Grafika</strong>
</p>
