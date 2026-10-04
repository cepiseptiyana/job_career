# Laravel Development Environment (Docker Setup)

Dokumen ini berisi panduan langkah demi langkah untuk menjalankan lingkungan pengembangan (_development environment_) Laravel menggunakan **Docker Compose**.

Konfigurasi ini mencakup layanan **Nginx**, **PHP-FPM** (lengkap dengan Xdebug), **Workspace Container** (untuk menjalankan Vite/Node), dan **MySQL 8.0**.

---

## 🛠️ Prasyarat (Prerequisites)

Pastikan perangkat Anda sudah menginstal:

-   **Docker Desktop v4.15.0 atau lebih baru** (atau Docker Engine di Linux)
-   **Docker Compose** (V2)

> **Catatan Linux:** Jika user Anda belum masuk grup `docker`, tambahkan `sudo` di depan setiap perintah `docker` pada panduan ini.

---

## 🚀 Cara Menjalankan Proyek

Jalankan semua perintah dari _root folder_ proyek, **sesuai urutan**. Dua hal penting:

-   `vendor/` dan `APP_KEY` harus sudah ada **sebelum** container dibuild. Jika tidak, container `php-fpm` berhenti (502 Bad Gateway) atau Laravel menampilkan error _key_.
-   Container membaca file `.env` **saat dibuat**. Karena itu `APP_KEY` dibuat sebelum build, bukan sesudahnya.

### Langkah 0: Pastikan Docker Engine Aktif

-   **macOS:**
    ```bash
    open -a Docker
    ```
-   **Linux:**
    ```bash
    sudo systemctl start docker
    ```
-   **Windows (PowerShell):**

    _Jika diinstal untuk semua pengguna (**All-Users**):_

    ```powershell
    Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe"
    ```

    _Jika diinstal khusus untuk pengguna saat ini (**Per-User**):_

    ```powershell
    Start-Process "$env:LOCALAPPDATA\Programs\DockerDesktop\Docker Desktop.exe"
    ```

    _Tunggu sekitar 10–20 detik sampai Docker Engine benar-benar siap._

### Langkah 1: Bersihkan Container dan Volume Lama

```bash
docker compose --file compose.dev.yaml down -v
```

_Langkah ini menghapus container dan data MySQL lama. Lewati jika ini instalasi pertama dan belum ada container yang pernah dijalankan._

### Langkah 2: Salin File Environment

```bash
cp .env.example .env
```

### Langkah 3: Edit File `.env`

```bash
nano .env
```

Pastikan nilai berikut ada, **masing-masing hanya satu kali** (jika ada baris ganda, baris paling bawah yang dipakai). Biarkan `APP_KEY=` kosong karena diisi pada Langkah 5.

```env
# Sesuaikan dengan hasil `id -u` dan `id -g` (Linux/macOS)
UID=1000
GID=1000

# Database MySQL
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret

# Tidak memakai Redis
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Simpan dengan `Ctrl+O`, `Enter`, lalu keluar dengan `Ctrl+X`.

**Hal yang wajib diperhatikan:**

-   `DB_HOST` harus `mysql` (nama service di Docker), **bukan** `localhost`.
-   `DB_USERNAME` **tidak boleh `root`**. Nilai ini dipakai sebagai `MYSQL_USER`, dan MySQL menolak start jika diisi `root`.
-   `DB_PASSWORD` **tidak boleh kosong**. Jika kosong, MySQL memakai password `root` sementara Laravel login dengan password kosong, sehingga koneksi ditolak.
-   Perubahan `DB_*` baru berlaku pada volume database yang bersih, jadi jalankan Langkah 1 jika MySQL pernah dijalankan dengan nilai berbeda.

### Langkah 4: Install Dependensi Composer

Gunakan image Composer resmi, sehingga tidak perlu container proyek:

```bash
docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app composer:2 composer install --ignore-platform-reqs
```

**Penjelasan Perintah:**

-   `-u $(id -u):$(id -g)`: Folder `vendor/` dimiliki oleh user Anda, bukan root.
-   `--ignore-platform-reqs`: Image Composer tidak memiliki semua ekstensi PHP proyek. Ekstensi yang sebenarnya tersedia di container `php-fpm`.

Pastikan hasilnya berhasil:

```bash
ls vendor/autoload.php
```

_Catatan Windows PowerShell: gunakan `docker run --rm -v "${PWD}:/app" -w /app composer:2 composer install --ignore-platform-reqs`._

### Langkah 5: Generate Application Key (Sekali Saja)

Dijalankan **sebelum** build, memakai image Composer yang sama:

```bash
docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app composer:2 php artisan key:generate
```

Cek bahwa key terisi dengan benar (hasil harus **51**):

```bash
awk -F= '/^APP_KEY=/{print length($0)-8}' .env
```

> ⚠️ **Jangan jalankan `key:generate` lebih dari sekali.** Jika key sudah terisi dan Anda menjalankannya lagi saat container sedang berjalan, nilai `APP_KEY` bisa rusak (_Unsupported cipher or incorrect key length_). Jika perlu mengulang, kosongkan lagi `APP_KEY=` di `.env` terlebih dahulu.

_Alternatif jika perintah di atas gagal: buat key dengan `echo "base64:$(openssl rand -base64 32)"`, lalu tempel hasilnya ke `APP_KEY=` di `.env`._

### Langkah 6: Build dan Jalankan Container

```bash
docker compose --file compose.dev.yaml up --build -d
```

**Penjelasan Perintah:**

-   `--file compose.dev.yaml`: Menggunakan file konfigurasi khusus development.
-   `up`: Membuat dan menyalakan container.
-   `--build`: Memaksa build ulang image sesuai Dockerfile.
-   `-d`: Berjalan di latar belakang (_detached mode_).

Tunggu sekitar 25 detik, lalu cek status:

```bash
docker compose --file compose.dev.yaml ps -a
```

Keempat service (`web`, `php-fpm`, `workspace`, `mysql`) harus berstatus **`Up`**.

### Langkah 7: Jalankan Migrasi Database

**Tunggu MySQL siap terlebih dahulu.** Pada instalasi pertama, MySQL butuh sekitar 20–40 detik untuk inisialisasi. Jika `migrate` dijalankan terlalu cepat, muncul error `Connection refused`. Cek log:

```bash
docker compose --file compose.dev.yaml logs mysql --tail 5
```

Lanjutkan jika sudah ada baris `ready for connections` dengan `port: 3306`. Lalu:

```bash
docker compose --file compose.dev.yaml exec php-fpm php artisan migrate
```

_Jika masih `Connection refused`, tunggu 15–20 detik lalu ulangi perintah `migrate`._

Setelah itu buka [http://localhost](http://localhost) dan refresh dengan `Ctrl+Shift+R`.

### Langkah 8: Menyalakan Server Frontend (Vite) via Workspace

Server aset **Vite** harus dinyalakan manual agar CSS/JS dapat dimuat browser.

1. Masuk ke container `workspace`:
    ```bash
    docker compose --file compose.dev.yaml exec -it workspace bash
    ```
2. Di dalam `/var/www`, jalankan:

    ```bash
    npm install && npm run dev -- --host
    ```

    _Catatan: Parameter `-- --host` wajib agar Vite di dalam Docker dapat diakses dari browser komputer Anda._

3. **Cara menghentikan Vite dan keluar dari Workspace:**
    - Tekan `Ctrl + C` untuk menghentikan Vite.
    - Ketik `exit` lalu tekan `Enter`.

---

## 🌐 Akses Layanan

-   **Aplikasi Web (Laravel):** [http://localhost](http://localhost) (Port `80`)
-   **Vite Dev Server (Frontend):** [http://localhost:5173](http://localhost:5173) (atau sesuai `VITE_PORT` di `.env`)
-   **MySQL Database:** `localhost` dengan Port `3306`

---

## 🐳 Struktur Layanan Docker (Overview)

1. **`web` (Nginx):** Meneruskan permintaan HTTP ke container PHP-FPM. Port `80`.
2. **`php-fpm`:** Mengeksekusi kode PHP Laravel, dilengkapi **Xdebug** untuk _debugging_ dan _profiling_.
3. **`workspace`:** Container interaktif untuk `npm`, Vite (Port `5173`), dan perintah pengembangan lainnya.
4. **`mysql`:** MySQL 8.0. Data disimpan persisten di volume `mysql_data`.

---

## 🛑 Menghentikan dan Menjalankan Kembali Proyek

### 1. Menghentikan Sementara

```bash
docker compose --file compose.dev.yaml stop
```

_Container hanya dinonaktifkan; konfigurasi dan data tetap tersimpan._

### 2. Jalankan Kembali (Resume)

```bash
docker compose --file compose.dev.yaml up -d
```

_Tidak perlu mengulang Langkah 2–7._

### 3. Hapus Total Container & Volume Database (`down -v`)

```bash
docker compose --file compose.dev.yaml down -v
```

_⚠️ **Peringatan:** Parameter `-v` menghapus volume `mysql_data`. Semua data di MySQL akan hilang permanen._

---

## 🔍 Troubleshooting

Cek status dan log container yang bermasalah:

```bash
docker compose --file compose.dev.yaml ps -a
docker compose --file compose.dev.yaml logs <nama-layanan> --tail 30
```

| Gejala | Penyebab | Solusi |
| --- | --- | --- |
| **502 Bad Gateway** di browser | Container `php-fpm` mati | Cek `logs php-fpm`, biasanya karena `vendor/` belum ada (ulangi Langkah 4) |
| Log `php-fpm`: `Failed opening required '/var/www/vendor/autoload.php'` | Composer belum diinstal | Jalankan Langkah 4, lalu `docker compose --file compose.dev.yaml up -d php-fpm` |
| `mysql` berstatus **Restarting** | `DB_USERNAME=root` atau `DB_PASSWORD` kosong | Perbaiki `.env` (Langkah 3), lalu `down -v` dan ulangi dari Langkah 6 |
| `Connection refused` saat migrate | MySQL belum selesai inisialisasi | Tunggu hingga log `mysql` menampilkan `ready for connections`, lalu ulangi `migrate` |
| `Access denied for user` saat migrate | Password di `.env` berbeda dengan yang tersimpan di volume lama | `down -v`, lalu ulangi dari Langkah 6 |
| **No application encryption key has been specified** | `APP_KEY` kosong saat container dibuat | Isi `APP_KEY` (Langkah 5), lalu `docker compose --file compose.dev.yaml up -d --force-recreate php-fpm` |
| **Unsupported cipher or incorrect key length** | `APP_KEY` rusak (misalnya `key:generate` dijalankan dua kali) | Kosongkan `APP_KEY=` di `.env`, ulangi Langkah 5, lalu `up -d --force-recreate php-fpm` |
| Error terkait Redis | `CACHE_STORE`, `SESSION_DRIVER`, atau `QUEUE_CONNECTION` masih `redis` | Ubah ke `file` / `sync` / `database` |
