<p align="center">
  <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTqx9hRtjkEr22Lb7D5tjDuCc3rSz6qgA26aAlqZSdr3Q&s=10" alt="Absensi Cipta Grafika Logo" width="120" height="120" />
</p>

<h1 align="center">Absensi Cipta Grafika</h1>

<p align="center">
  Sistem Informasi Manajemen Presensi, Payroll, Lembur & Leaderboard Karyawan berbasis QR Code dan Real-Time GPS Geolocation untuk CV. Cipta Grafika.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.0-FF2D20?logo=laravel" alt="Laravel 12" />
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php" alt="PHP 8.2+" />
  <img src="https://img.shields.io/badge/Livewire-3.0-FB70A9?logo=livewire" alt="Livewire 3" />
  <img src="https://img.shields.io/badge/Jetstream-5.1-4A5568" alt="Jetstream 5" />
  <img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql" alt="MySQL 8" />
  <img src="https://img.shields.io/badge/Tailwind_CSS-3.4-06B6D4?logo=tailwindcss" alt="Tailwind CSS 3.4" />
  <img src="https://img.shields.io/badge/Vite-6.4-646CFF?logo=vite" alt="Vite 6.4" />
  <img src="https://img.shields.io/badge/Testing-Pest_3-8B5CF6?logo=pest" alt="Pest 3" />
  <img src="https://img.shields.io/badge/License-MIT-green" alt="License" />
</p>

---

## ✨ Fitur Utama & Modul Sistem

### 📍 1. Presensi QR Code & Real-Time GPS Geolocation
- **Absensi QR Code Instan** — Karyawan melakukan scan QR Code lokasi/kantor untuk *check-in* & *check-out*.
- **Validasi GPS Radius Precision** — Verifikasi koordinat lokasi karyawan menggunakan **Leaflet.js + OpenStreetMap** secara presisi.
- **Kamera Scanner QR Code High-Performance** — Pemindai kamera reaktif berbasis `html5-qrcode` yang cepat dan responsif.
- **Deduction & Penalty Calculator** — Penghitungan otomatis denda keterlambatan, potongan absen, WFH, dan pengajuan IMP.

### 🏆 2. Real-Time Leaderboard Kerajinan (Top 5 Early Birds)
- **Leaderboard Real-Time via Server-Sent Events (SSE)** — Pembaruan leaderboard secara simultan tanpa *interval polling* menggunakan koneksi SSE (`/api/leaderboard/stream`).
- **Skor & Poin Presensi Bulanan** — Akumulasi menit kedatangan lebih awal (*early arrival minutes*), jumlah hari hadir, dan kalkulasi poin performa otomatis per periode bulan.
- **Peringkat Top 5 per Divisi** — Menampilkan Top 5 Karyawan Terrajin di halaman Beranda (`/home`) dilengkapi lencana medali Gold 🥇, Silver 🥈, Bronze 🥉, dan indikator Pts pastel green.
- **SuperAdmin Recalculation Tool** — Fitur hitung ulang statistik leaderboard bulanan dengan 1-klik di panel Master Data.

### 💬 3. Dynamic Scan Feedback & Ucapan Motivasi
- **Ucapan Dynamic Randomizer** — Pop-up feedback otomatis saat scan berdasarkan kategori waktu (`super_early`, `early`, `on_time`, `late_mild`, `late_severe`, `out`).
- **Personalized Placeholder `{name}`** — Menyebut nama depan karyawan secara ramah pada setiap pesan feedback.
- **SuperAdmin Scan Feedback CRUD** — Fitur Kelola Ucapan di Master Data khusus SuperAdmin untuk menambah, mengedit, mengaktifkan/menonaktifkan, dan menghapus variasi ucapan.

### 💰 4. Penggajian & Payroll Management
- **Dashboard Payroll** — Ringkasan statistik pengeluaran gaji, total potongan, dan tabungan karyawan.
- **Manajemen Gaji Karyawan** — Pengaturan komponen gaji pokok, tunjangan, dan potongan tetap per karyawan.
- **Metode Pembayaran (Payment Methods)** — Pengelolaan rekening bank & dompet digital pembayaran gaji.
- **Riwayat Gaji & Cetak Slip Gaji (Payslips)** — Generasi riwayat gaji bulanan dan cetak Slip Gaji PDF untuk karyawan.
- **Tabungan Karyawan (Savings & Transactions)** — Manajemen saldo tabungan dan riwayat setoran/penarikan tabungan.
- **Pinjaman Karyawan (Loans)** — Pengelolaan pinjaman/kasbon karyawan beserta pemotongan gaji otomatis.

### ⏰ 5. Manajemen Lembur (Overtime Management)
- **Pengajuan Lembur Karyawan** — Portal pengajuan jam lembur karyawan beserta deskripsi pekerjaan.
- **Tarif Lembur Dinamis (Overtime Rates)** — Pengaturan tarif lembur per jam di Master Data oleh SuperAdmin.
- **Kalkulasi & Approval Lembur** — Verifikasi, persetujuan admin, dan penghitungan kompensasi lembur otomatis.

### 📂 6. Import & Export Excel & Laporan PDF
- **Import/Export Data Karyawan & Absensi** — Fitur ekspor/impor massal data karyawan dan presensi via berkas Excel (`.xlsx`).
- **Import/Export Roster Jadwal Kerja (Work Schedules)** — Pengaturan dan impor jadwal kerja shift karyawan bulanan via Excel.
- **Import/Export Payroll Data** — Ekspor & impor data gaji, metode pembayaran, dan tabungan karyawan.
- **Cetak Laporan PDF** — Ekspor rekapitulasi absensi dan slip gaji ke format PDF menggunakan **DomPDF**.

### 🔒 7. Keamanan & Proteksi Search Engine (Robots.txt & Meta Noindex)
- **Robots.txt Security Rules** — Konfigurasi `public/robots.txt` publik untuk memblokir crawling search engine pada `/login`, `/register`, `/home`, `/hr/`, `/user/`, dan `/api/`.
- **Meta Noindex & Nofollow** — Proteksi header HTML berlapis pada `guest.blade.php` dan `app.blade.php`.
- **Level Akses Tiga Peran (RBAC)** — Otorisasi ketat untuk **Karyawan**, **Admin Divisi**, dan **Superadmin**.
- **Two-Factor Authentication (2FA)** — Keamanan login berlapis via Laravel Jetstream & Sanctum.

---

## 🛠 Tech Stack & Versi

| Layer | Teknologi / Library | Versi | Deskripsi |
|---|---|---|---|
| **Framework** | [Laravel](https://laravel.com) | `^12.0` | Framework utama PHP versi 12 |
| **Bahasa** | PHP | `^8.2` | Bahasa pemrograman backend |
| **Reaktif UI** | [Livewire](https://livewire.laravel.com) | `^3.0` | Full-stack reaktif framework untuk Laravel |
| **Auth & Profile** | [Laravel Jetstream](https://jetstream.laravel.com) | `^5.1` | Sistem autentikasi, profil & 2FA |
| **API Security** | [Laravel Sanctum](https://laravel.com/docs/sanctum) | `^4.0` | Autentikasi token API & SPA |
| **Database** | MySQL / MariaDB | `8.0+` | Database relasional |
| **Real-time SSE** | Server-Sent Events (Native PHP Stream) | — | Push update leaderboard real-time tanpa polling |
| **Styling** | [Tailwind CSS](https://tailwindcss.com) | `^3.4` | Utility-first CSS framework |
| **Build Tool** | [Vite](https://vitejs.dev) | `^6.4` | Frontend bundler & HMR dev server |
| **Selector UI** | [Tom Select](https://tom-select.js.org) | `^2.6` | Component autocomplete & select box |
| **Peta & GPS** | [Leaflet.js](https://leafletjs.com) + OpenStreetMap | `1.9.4` | Peta interaktif & lokasi GPS |
| **QR Code Scanner**| [Html5-Qrcode](https://github.com/mebjas/html5-qrcode) | `2.3.8` | Pemindai QR Code via kamera browser |
| **Generator QR** | [Endroid QR Code](https://github.com/endroid/qr-code) | `^5.0` | Builder gambar QR Code barcode |
| **Export PDF** | [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) | `^3.0` | Generator laporan & slip gaji PDF |
| **Excel Export/Import**| [Maatwebsite Excel](https://laravel-excel.com) | `^3.1` | Generator & parser spreadsheet Excel |
| **Kalkulasi Jarak** | [ballen/distical](https://packagist.org/packages/ballen/distical) | `^3.1` | Perhitungan jarak koordinat GPS |
| **Image Processing**| [Intervention Image](https://image.intervention.io) | `^3.6` | Pemrosesan foto profil & avatar |
| **Testing** | [Pest PHP](https://pestphp.com) | `^3.0` | Framework pengujian otomatis (Unit & Feature) |

---

## 🚀 Instalasi & Konfigurasi Lokal

### Prasyarat System

Pastikan perangkat Anda sudah terpasang:
- **PHP** `>= 8.2` (ekstensi: `mbstring`, `xml`, `pdo_mysql`, `zip`, `gd`, `fileinfo`, `ctype`, `curl`)
- **Composer** `>= 2.x`
- **Node.js** `>= 18.x` & **npm**
- **MySQL** `>= 8.0` atau **MariaDB** `>= 10.4`

---

### Langkah Instalasi

#### 1. Clone Repository
```bash
git clone https://github.com/Cipta-Grafika/absensicipta.git
cd absensicipta
```

#### 2. Install Dependency PHP & Node.js
```bash
composer install
npm install
```

#### 3. Konfigurasi Environment File
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Sesuaikan pengaturan database dan URL di file `.env`:
```env
APP_NAME="Absensi Karyawan"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_absensi_karyawan
DB_USERNAME=root
DB_PASSWORD=
```

#### 4. Generate Application Key
```bash
php artisan key:generate --ansi
```

#### 5. Migrasi & Seed Database
Jalankan migrasi untuk membuat 21+ tabel database:
```bash
# Jalankan migrasi dan seeder awal (Admin, Superadmin & Master Data)
php artisan migrate --seed

# (Opsional) Jalankan seeder data dummy karyawan & presensi lengkap
php artisan db:seed FakeDataSeeder
```

> 🔐 **Akun Default Kredensial (Setelah Seeding):**
>
> | Role | Email | Password | Hak Akses |
> |---|---|---|---|
> | **Superadmin** | `superadmin@example.com` | `password` | Akses Penuh Seluruh Sistem & Master Data |
> | **Admin** | `admin@example.com` | `password` | Akses Manajemen Presensi & Karyawan Divisi |
> | **Karyawan** | `karyawan@example.com` | `password` | Scan Absensi, Pengajuan Izin/Lembur, Slip Gaji |

#### 6. Build Aset Frontend & Jalankan Server
```bash
# Mode Development (Vite HMR)
npm run dev

# Di terminal terpisah, jalankan server Laravel:
php artisan serve
```
Akses aplikasi di browser: **[http://localhost:8000](http://localhost:8000)**.

---

## 📋 Struktur Menu & Modul Navigasi

```
│
├── 🏠 Beranda (/home)
│   ├── Scanner QR Code & Lokasi GPS Live
│   ├── Status Presensi Hari Ini & Penghitungan Potongan
│   ├── Dynamic Scan Feedback Greetings Modal
│   └── Real-time Leaderboard Kerajinan (Top 5 Early Birds)
│
├── 📊 Presensi (/hr/attendances)
│   ├── Rekapitulasi Presensi Karyawan
│   ├── Filtering Status (Hadirlah, Terlambat, Izin, Sakit, Cuti, IMP, WFH, Absent)
│   ├── Filter Scope (Hari ini, Minggu ini, Bulan ini, Custom Range)
│   └── Export Data Presensi Excel / PDF
│
├── 👥 Karyawan (/hr/users)
│   ├── Daftar & CRUD Karyawan
│   └── Import & Export Data Karyawan Excel
│
├── 💳 Payroll & Gaji (/payroll)
│   ├── Dashboard Payroll (/payroll)
│   ├── Gaji Karyawan (/payroll/employee-salaries)
│   ├── Metode Pembayaran (/payroll/payment-methods)
│   ├── Riwayat Gaji (/payroll/history)
│   ├── Tabungan Karyawan (/payroll/savings)
│   ├── Transaksi Tabungan (/payroll/saving-transactions)
│   ├── Pinjaman Karyawan (/payroll/loans)
│   └── Slip Gaji Karyawan (/user/payslips)
│
├── ⏰ Lembur (/hr/overtimes & /user/overtimes)
│   ├── Pengajuan Lembur Karyawan
│   └── Persetujuan & Tarif Lembur
│
├── 🗂️ Master Data (/hr/masterdata/*) - khusus SuperAdmin
│   ├── Divisi (/hr/masterdata/divisions)
│   ├── Jabatan (/hr/masterdata/job-titles)
│   ├── Pendidikan (/hr/masterdata/educations)
│   ├── Shift Kerja (/hr/masterdata/shifts)
│   ├── QR Code Barcode Lokasi (/hr/barcodes)
│   ├── Leaderboard Kerajinan (/hr/masterdata/leaderboard)
│   └── Ucapan Scan Feedback (/hr/masterdata/scan-feedback)
│
└── 📥 Import & Export (/hr/import-export/*)
    ├── Import/Export Karyawan
    ├── Import/Export Presensi
    └── Import/Export Roster Jadwal Kerja (/hr/import-export/work-schedules)
```

---

## 🧪 Pengujian Otomatis (Automated Testing)

Proyek ini dilengkapi dengan suite pengujian otomatis berbasis **Pest 3 / PHPUnit**.

```bash
# Jalankan seluruh pengujian otomatis
php artisan test

# Jalankan pengujian khusus Scan Feedback & Leaderboard
php artisan test --filter=ScanFeedbackTest
```

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah **MIT License**.

---

<p align="center">
  Dibuat dengan 🔥 oleh <a href="https://github.com/astrocoding">Zaenal Alfian</a> untuk <strong>Cipta Grafika</strong>
</p>
