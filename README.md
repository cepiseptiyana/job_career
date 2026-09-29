# Laravel Development Environment (Docker Setup)

Dokumen ini berisi panduan langkah demi langkah untuk menjalankan lingkungan pengembangan (_development environment_) Laravel menggunakan **Docker Compose**.

Konfigurasi ini sudah mencakup layanan **Nginx**, **PHP-FPM** (lengkap dengan Xdebug), **Workspace Container** (untuk menjalankan Vite/Node/Composer), dan **MySQL 8.0**.

---

## 🛠️ Prasyarat (Prerequisites)

Sebelum memulai, pastikan perangkat Anda sudah menginstal aplikasi berikut:

-   **Docker Desktop v4.15.0 atau lebih baru** (atau Docker Engine di Linux)
-   **Docker Compose** (V2)

---

## 🚀 Cara Menjalankan Proyek

Ikuti langkah-langkah berikut melalui terminal untuk memastikan Docker aktif, membangun container, menyiapkan database, dan menyalakan server frontend:

### Langkah 0: Pastikan Docker Engine Aktif

Sebelum menjalankan perintah Docker Compose, aplikasi _Docker Engine_ atau _Docker Desktop_ harus dalam posisi aktif/berjalan di sistem Anda. Anda bisa langsung menyalakannya melalui terminal menggunakan perintah berikut:

-   **Bagi Pengguna macOS:**
    ```bash
    open -a Docker
    ```
-   **Bagi Pengguna Linux:**
    ```bash
    sudo systemctl start docker
    ```
-   **Bagi Pengguna Windows (PowerShell):**
    Sesuaikan perintah berdasarkan tipe instalasi Docker Desktop Anda:

    _Jika diinstal untuk semua pengguna (**All-Users**):_

    ```powershell
    Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe"
    ```

    _Jika diinstal khusus untuk pengguna saat ini saja (**Per-User**):_

    ```powershell
    Start-Process "$env:LOCALAPPDATA\Programs\DockerDesktop\Docker Desktop.exe"
    ```

    _Tunggu sekitar 10–20 detik sampai Docker Engine benar-benar siap dan aktif di latar belakang._

### Langkah 1: Build dan Jalankan Container

Jalankan perintah berikut pada terminal di _root folder_ proyek Anda untuk mengunduh _image_, membangun (_build_), dan menjalankan semua layanan di latar belakang (_detached mode_):

```bash
docker compose --file compose.dev.yaml up --build -d
```

**Penjelasan Perintah:**

-   `--file compose.dev.yaml`: Menginstruksikan Docker untuk menggunakan file konfigurasi spesifik (bukan file `compose.yaml` standar).
-   `up`: Perintah untuk membuat dan menyalakan container.
-   `--build`: Memaksa Docker untuk membangun ulang konfigurasi Dockerfile (sangat berguna jika ada perubahan pada konfigurasi PHP atau Workspace).
-   `-d`: Menjalankan container di latar belakang (_detached mode_), sehingga terminal Anda tetap bisa digunakan untuk perintah lain.

### Langkah 2: Jalankan Migrasi Database

Setelah semua container berhasil berjalan (terutama layanan `mysql` dan `php-fpm`), buat struktur tabel database Anda dengan menjalankan perintah ini:

```bash
docker compose --file compose.dev.yaml exec php-fpm php artisan migrate
```

**Penjelasan Perintah:**

-   `exec php-fpm`: Menginstruksikan Docker untuk masuk dan mengeksekusi perintah di dalam container `php-fpm` yang sedang berjalan.
-   `php artisan migrate`: Perintah standar Laravel untuk menjalankan file migrasi database.

### Langkah 3: Menyalakan Server Frontend (Vite) via Workspace

Meskipun container `workspace` sudah menyala, server aset **Vite** di dalamnya harus dipicu secara manual agar CSS/JS pada UI Blade Anda dapat dimuat dengan benar oleh _browser_.

1. Masuk ke dalam terminal container `workspace`:
    ```bash
    docker compose --file compose.dev.yaml exec -it workspace bash
    ```
2. Setelah masuk dan berada di dalam path `/var/www`, jalankan perintah berikut untuk menginstal package Node.js dan menyalakan server Vite:

    ```bash
    npm install && npm run dev -- --host
    ```

    _Catatan: Parameter `-- --host` wajib digunakan agar server Vite di dalam Docker dapat diakses dari browser komputer asli Anda._

3. **Cara Menonaktifkan Vite dan Keluar dari Workspace:**
   Jika Anda selesai bekerja dan ingin keluar dari terminal container tersebut, lakukan langkah berikut:
    - Tekan tombol `Ctrl + C` pada keyboard untuk menghentikan server Vite.
    - Ketik perintah `exit` lalu tekan `Enter` untuk keluar dan kembali ke terminal komputer asli Anda.

### 🔍 Cara Mengecek Status Container

Untuk memastikan semua container (`web`, `php-fpm`, `workspace`, dan `mysql`) sudah berjalan dengan benar dan melihat port yang aktif, jalankan perintah berikut di terminal komputer asli Anda:

```bash
docker compose --file compose.dev.yaml ps
```

**Cara Membaca Hasilnya:**

-   Cari kolom **STATUS** atau **STATE**. Jika tertulis **`Up`** atau **`Running`**, berarti container berjalan dengan lancar.
-   Jika ada container yang berstatus **`Exited`**, berarti terjadi masalah pada layanan tersebut (Anda bisa mengecek penyebabnya dengan perintah `docker compose --file compose.dev.yaml logs <nama-layanan>`).

---

## 🌐 Akses Layanan

Setelah langkah-langkah di atas selesai dijalankan, Anda dapat mengakses proyek melalui peramban (_browser_) dengan alamat berikut:

-   **Aplikasi Web (Laravel):** [http://localhost](http://localhost) (Port `80`)
-   **Vite Dev Server (Frontend):** [http://localhost:5173](http://localhost:5173) (Atau sesuai variabel `VITE_PORT` di `.env` Anda)
-   **MySQL Database:** `localhost` dengan Port `3306`

---

## 🐳 Struktur Layanan Docker (Overview)

File `compose.dev.yaml` Anda mengelola 4 layanan utama:

1. **`web` (Nginx):** Server web yang meneruskan permintaan HTTP ke container PHP-FPM. Menggunakan port standar `80`.
2. **`php-fpm`:** Container utama yang mengeksekusi kode PHP Laravel Anda. Sudah dilengkapi dengan **Xdebug** untuk keperluan _debugging_ dan _profiling_ kode.
3. **`workspace`:** Container interaktif untuk kebutuhan pengembangan seperti menjalankan perintah `composer`, `npm`, `php artisan`, atau menjalankan server frontend **Vite** (Port `5173`).
4. **`mysql`:** Database server menggunakan MySQL versi 8.0. Data disimpan secara persisten di dalam volume komputer Anda (`mysql_data`), sehingga data tidak akan hilang saat container dimatikan.

---

## 🛑 Menghentikan dan Menjalankan Kembali Proyek

Tergantung pada kebutuhan Anda, gunakan salah satu metode di bawah ini untuk mengelola status container:

### 1. Menghentikan Sementara (Temporary Stop)

Jika Anda selesai bekerja dan ingin menghentikan proyek tanpa menghapus container, jalankan perintah berikut:

```bash
docker compose --file compose.dev.yaml stop
```

_Perintah ini hanya menonaktifkan container. Semua konfigurasi dan status container terakhir tetap tersimpan._

### 2. Jalankan Kembali (Resume)

Untuk melanjutkan pekerjaan setelah proyek dihentikan dengan perintah `stop`, Anda cukup menjalankan perintah ini (tanpa perlu melakukan _build_ ulang):

```bash
docker compose --file compose.dev.yaml up -d
```

### 3. Hapus Total Container & Volume Database (`down -v`)

Jika Anda ingin membersihkan lingkungan pengembangan secara total, menghentikan proyek, sekaligus **menghapus semua data di dalam database (bersih total)**, jalankan perintah berikut:

```bash
docker compose --file compose.dev.yaml down -v
```

_⚠️ **Peringatan:** Parameter `-v` akan menghapus volume `mysql_data`. Semua data dan tabel yang sudah Anda buat di MySQL akan hilang permanen._
