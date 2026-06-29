# Aplikasi Web Absensi Karyawan QR Code GPS

Aplikasi web absensi karyawan menggunakan QR Code dan GPS.

## Teknologi yang Digunakan

* [Laravel 11](https://laravel.com/)
* [Laravel Jetstream](https://jetstream.laravel.com/)
* [Endroid QR Code](https://github.com/endroid/qr-code)
* [Leaflet.js](https://leafletjs.com/)
* [OpenStreetMap](https://www.openstreetmap.org/)
* MySQL/MariaDB

## Instalasi

### Prasyarat

* [Composer](https://getcomposer.org)
* [NPM & Node.js](https://nodejs.org)
* PHP 8.2 atau lebih tinggi
* MySQL/MariaDB

---

1. Clone/download repository ini
2. Jalankan perintah `composer run-script post-root-package-install` untuk membuat file `.env`
3. Jalankan perintah `composer install` untuk menginstalasi dependency
4. Jalankan perintah `npm install` untuk menginstalasi dependency Javascript
5. Jalankan perintah `php artisan key:generate --ansi --force` untuk membuat key aplikasi
6. Jalankan perintah `php artisan migrate` untuk membuat tabel databasex
7. Jalankan perintah `npm run build` untuk membuat file css dan javascript yang diperlukan
8. Jalankan perintah `php artisan serve` untuk menjalankan aplikasi

### Seeder

Pilih salah satu opsi berikut:

* Jalankan perintah `php artisan db:seed DatabaseSeeder` untuk menyiapkan data awal
* Jalankan perintah `php artisan db:seed FakeDataSeeder` untuk menyiapkan data awal beserta data dummy (absensi & karyawan)
