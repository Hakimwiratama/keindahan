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
