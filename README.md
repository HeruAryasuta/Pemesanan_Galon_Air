<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Route optimization (TSP + OSRM)

Admin dapat membuka planner rute untuk memilih pengantaran aktif dan melihat pratinjau urutan, jarak, durasi, serta geometri jalan sebelum menetapkan rute ke kurir. Planner menggunakan OSRM Table untuk membuat matriks jarak jalan, lalu menyelesaikan TSP terbuka dari depo. Held-Karp dipakai untuk maksimal 14 pengantaran; jumlah yang lebih besar memakai nearest-neighbor dan 2-opt. Urutan terpilih dikirim ke OSRM Route untuk geometri dan estimasi durasi. Rute tidak kembali ke depo dan durasi tidak memperhitungkan lalu lintas langsung.

Configure these values in `.env`:

```dotenv
OSRM_BASE_URL=http://localhost:5000
ROUTE_DEPOT_LATITUDE=-7.3414987
ROUTE_DEPOT_LONGITUDE=112.7677984
```

Jalankan server OSRM dengan profil `driving` di URL yang dikonfigurasi. Koordinat alamat pelanggan dikirim ke server OSRM, jadi gunakan endpoint tepercaya yang Anda kelola. Peta planner menggunakan tile OpenStreetMap di browser.

### Titik alamat otomatis

Di `/addresses`, pengguna dapat memakai lokasi perangkat atau memilih/menggeser marker pada peta. Koordinat mengisi otomatis dari lokasi saat ini; pemilihan titik juga meminta reverse geocoding agar alamat lengkap (dan label bila tersedia serta masih kosong) terisi. Hasilnya tetap dapat diedit sebelum disimpan.

Reverse geocoding menggunakan layanan publik Nominatim. Konfigurasikan `NOMINATIM_BASE_URL`, `NOMINATIM_USER_AGENT` yang deskriptif, dan secara opsional `NOMINATIM_EMAIL`. Koordinat yang dipilih dikirim ke Nominatim; gunakan layanan sesuai kebijakan penggunaan Nominatim. Jika geocoding gagal atau alamat tidak ditemukan, pengguna dapat mengisi alamat secara manual. Akses lokasi browser memerlukan izin pengguna dan secure context (HTTPS atau `localhost`).

### Rute dari lokasi kurir

Pada detail pengantaran aktif, kurir dapat menekan **Tampilkan rute ke tujuan**. Browser meminta lokasi kurir pada saat itu, lalu aplikasi meminta OSRM Route untuk menghitung jalan langsung ke koordinat alamat tujuan. Peta menampilkan garis rute serta jarak dan estimasi waktu. Lokasi tidak dilacak terus-menerus dan tidak disimpan oleh fitur ini. Tombol memerlukan izin lokasi browser, koordinat tujuan, koneksi aplikasi ke OSRM, serta pengantaran yang ditugaskan kepada kurir yang sedang masuk.

## Menyiapkan data demo

Seeder katalog produk dapat dijalankan secara terpisah dan tidak menimpa produk yang sudah ada:

```sh
php artisan db:seed --class=ProductCatalogSeeder
```

`DemoCourierRouteSeeder` membuat pelanggan, alamat, pesanan, pengantaran, dan rute OSRM demo. Jalankan setelah tersedia akun kurir dengan email `kurir.demo.20261003@example.test`, produk aktif `galon-aqua-19-liter`, serta OSRM yang dapat diakses aplikasi:

```sh
php artisan db:seed --class=DemoCourierRouteSeeder
```

Seeder aman dijalankan ulang saat rute demo sudah dibuat. Seeder akan berhenti dengan pesan kesalahan jika akun kurir belum tersedia, data pesanan demo telah berubah, OSRM tidak dapat menghitung rute, atau kurir sudah memiliki rute pada hari ini. Data akun dan rute demo hanya untuk pengembangan; jangan gunakan kredensial atau data demo pada deployment produksi.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
