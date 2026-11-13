# Memory Vault

Aplikasi web Laravel untuk mengelompokkan foto, video, dan catatan kenangan ke dalam folder privat setiap akun.

## Persyaratan

- PHP 8.2 atau lebih baru
- Composer
- Ekstensi PHP `fileinfo`, `mbstring`, `openssl`, `pdo_sqlite`, dan `sqlite3`

## Menjalankan aplikasi

```powershell
composer install
Copy-Item .env.example .env
New-Item -ItemType File -Path database/database.sqlite
php artisan key:generate
php artisan migrate
php artisan serve
```

Buka alamat yang ditampilkan oleh `php artisan serve`, lalu daftar untuk membuat akun.

## Deployment di EthioDeploy

`database/*.sqlite` dan `.env` tidak dimasukkan ke Git. Karena itu, deployment harus memakai environment variables dari dashboard dan database SQLite pada disk persisten.

Atur environment variables berikut di dashboard:

| Nama | Nilai |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | URL HTTPS aplikasi |
| `APP_KEY` | Gunakan key yang sudah dipakai aplikasi; jangan jalankan `key:generate` pada database yang sudah berisi data |
| `DB_CONNECTION` | `sqlite` |
| `DB_DATABASE` | Path absolut ke file SQLite pada disk persisten EthioDeploy |
| `SESSION_DRIVER` | `file` |
| `CACHE_STORE` | `file` |
| `FILESYSTEM_DISK` | `local` |
| `SESSION_SECURE_COOKIE` | `true` untuk situs HTTPS |

Sebelum deployment pertama:

1. Pastikan PHP 8.2 atau lebih baru tersedia beserta `pdo_sqlite`, `sqlite3`, `fileinfo`, `mbstring`, `openssl`, dan `session`.
2. Pastikan document root Nginx/PHP-FPM mengarah ke direktori `public/`.
3. Pastikan disk persisten sudah dipasang. Untuk database yang telah berisi data, gunakan file database lama dari disk persisten atau backup yang benar; jangan membuat database baru pada path pengganti.
4. Pastikan user PHP-FPM dapat menulis ke direktori `storage/` dan `bootstrap/cache/`. Unggahan privat disimpan di `storage/app/private`.
5. Jalankan `composer install --no-dev --optimize-autoloader --no-interaction`, lalu `php artisan config:clear`.
6. Periksa `php artisan migrate:status`. Backup database sebelum menjalankan `php artisan migrate --force`. Migration `2026_11_01_000005_remove_default_albums` memindahkan kenangan dari album bernama “Sayang” atau “Kuliah” ke “Kenangan lama”, lalu menghapus baris album tersebut; tinjau dampaknya untuk data produksi sebelum menerapkannya.
7. Setelah environment variables benar, jalankan `php artisan config:cache`.

Jangan mengaktifkan `APP_DEBUG` di production. Perintah `php artisan key:generate` hanya untuk instalasi baru; mengganti key pada aplikasi yang sudah berjalan dapat membuat sesi dan data terenkripsi lama tidak dapat dibaca.

Pengaturan PHP dan batas body Nginx biasanya hanya tersedia di dashboard EthioDeploy. Untuk unggahan sampai 1 GB, sesuaikan batas PHP dan proxy sesuai bagian Privasi dan penyimpanan di bawah; batas paket hosting tetap dapat lebih rendah.

## Privasi dan penyimpanan

- Setiap catatan dibuat melalui relasi `User::memories()` sehingga `user_id` berasal dari akun yang sedang masuk.
- Daftar kenangan diambil dari relasi pengguna aktif, bukan dari seluruh tabel.
- Detail, unduhan, dan penghapusan hanya mencari kenangan melalui relasi pemilik; resource akun lain menghasilkan 404.
- Policy `MemoryPolicy` juga menolak akses lintas akun sebagai pertahanan tambahan.
- Setiap akun membuat folder sendiri (misalnya “Sayang” atau “Kuliah”). Dasbor menampilkan folder, dan isi setiap folder hanya menampilkan kenangan di folder itu.
- Pemilik dapat menghapus folder. Setelah konfirmasi, semua catatan dan file dalam folder tersebut dihapus secara permanen.
- Satu kali unggah dapat berisi beberapa foto atau beberapa video. Setiap file menjadi kenangan terpisah di folder aktif.
- Migrasi folder mempertahankan kenangan lama dengan memasukkannya ke folder “Kenangan lama” untuk akun pemilik.
- File disimpan di disk `local` pada `storage/app/private`, tidak di direktori publik. Unduhan dilayani oleh endpoint terautentikasi dan diperiksa kepemilikannya.
- Jenis foto menerima JPG/JPEG, PNG, GIF, WEBP; video menerima MP4, MOV, AVI, MKV, WEBM; batas ukuran 1 GB (1.024 MB).
- Agar PHP menerima unggahan sebesar batas tersebut, atur `upload_max_filesize = 1024M` dan `post_max_size = 1100M` pada `php.ini`. Mulai ulang server setelah mengubah konfigurasi.

## Pengujian

```powershell
composer test
```

Tes fitur memeriksa isolasi akun dan folder, pembuatan album, unggah beberapa file, batas unggahan, serta unduhan privat.
