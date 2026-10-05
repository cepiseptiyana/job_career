# Laravel Development Environment (Docker Setup for Onboarding)

Dokumen ini berisi panduan langkah demi langkah bagi tim pengembang (*developer*) untuk menjalankan lingkungan pengembangan (*development environment*) Laravel secara instan menggunakan **Docker Compose** dan **Docker Hub**.

Konfigurasi ini memanfaatkan *image* matang yang sudah di-*push* ke Docker Hub (`cepiseptiyana/job_career-*`), sehingga Anda **tidak perlu melakukan proses build lokal** atau menginstal dependensi dari luar container.

---

## 🛠️ Prasyarat (Prerequisites)

Pastikan perangkat Anda sudah menginstal:
- **Docker Engine / Docker Desktop** (V20.x atau lebih baru)
- **Docker Compose** (V2)
- **Git** (untuk melakukan clone kode proyek)

> **Catatan Linux:** Jika user Anda belum masuk grup `docker`, tambahkan `sudo` di depan setiap perintah `docker` / `docker compose` pada panduan ini.

---

## 🚀 Cara Menjalankan Proyek (Onboarding Steps)

Jalankan semua perintah ini dari dalam *root folder* proyek hasil clone Anda secara berurutan.

### Langkah 1: Pastikan Docker Engine Aktif
- **Linux:**
  ```bash
  sudo systemctl start docker
  ```
- **macOS:** `open -a Docker`
- **Windows (PowerShell):** `Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe"`

### Langkah 2: Bersihkan Sisa Container Lama (Jika Ada)
Jika Anda pernah menjalankan container proyek lain yang bentrok, bersihkan terlebih dahulu:
```bash
docker compose --file compose.dev.yaml down -v
```

### Langkah 3: Salin dan Sesuaikan File Environment
1. Salin file contoh environment:
   ```bash
   cp .env.example .env
   ```
2. Buka dan edit file `.env`:
   ```bash
   nano .env
   ```
3. Pastikan konfigurasi Database dan User ID sesuai:
   ```env
   # Sesuaikan dengan hasil perintah `id -u` dan `id -g` di Linux/macOS Anda
   UID=1000
   GID=1000

   # Konfigurasi Database (Jangan diubah karena sudah serasi dengan Docker)
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=laravel
   DB_PASSWORD=root
   ```

### Langkah 4: Jalankan Container (Otomatis Pull dari Docker Hub)
Karena konfigurasi `compose.dev.yaml` sudah dilengkapi dengan jalur *image* cloud, Anda **tidak perlu menjalankan perintah `--build`**. Cukup ketik:
```bash
docker compose --file compose.dev.yaml up -d
```
*Docker akan otomatis mendeteksi dan mengunduh (pulling) image `cepiseptiyana/job_career-php-fpm:dev` dan `workspace:dev` langsung dari internet dalam hitungan detik.*

### Langkah 5: Generate Application Key Internal
Karena kode proyek murni dibaca dari folder lokal via *bind-mount*, generate `APP_KEY` Laravel Anda langsung dari dalam container yang sudah aktif:
```bash
docker compose --file compose.dev.yaml exec php-fpm php artisan key:generate
```

### Langkah 6: Jalankan Migrasi Database
Tunggu sekitar 15–30 detik agar MySQL selesai melakukan inisialisasi awal di latar belakang. Setelah MySQL siap, buat struktur tabelnya dengan perintah:
```bash
docker compose --file compose.dev.yaml exec php-fpm php artisan migrate
```
*Jika muncul pesan "Connection refused", tunggu 10 detik lalu ulangi perintah di atas.*

### Langkah 7: Jalankan Server Frontend (Vite)
Aset CSS/JS Laravel harus dikompilasi secara dinamis agar bisa tampil di browser:
1. Masuk ke terminal container `workspace`:
   ```bash
   docker compose --file compose.dev.yaml exec -it workspace bash
   ```
2. Jalankan server lokal Vite (Dependensi `node_modules` akan otomatis terinstal jika belum ada):
   ```bash
   npm install && npm run dev -- --host
   ```
3. Keluar dari Workspace: Tekan `Ctrl + C` untuk stop Vite, lalu ketik `exit`.

---

## 🌐 Akses Layanan

- **Aplikasi Web (Laravel):** [http://localhost](http://localhost) (Port `80`)
- **Vite Dev Server (Frontend):** [http://localhost:5173](http://localhost:5173)
- **Database MySQL:** Host `localhost` dengan Port `3306`

---

## 🐳 Informasi Khusus untuk Tim (Notes for Developers)

- **Kapan perintah `build` digunakan?**
  Perintah `docker compose --file compose.dev.yaml build` **khusus digunakan oleh Leader / DevOps** jika ada perubahan mendasar pada file `Dockerfile` (seperti menambah ekstensi PHP baru atau mengganti versi Node). Sebagai tim developer biasa, Anda cukup melakukan `up -d` untuk menarik pembaruan.
- **Fitur Live-Coding:**
  Meskipun *image* ditarik dari Docker Hub, folder lokal Anda tetap terhubung via *bind-mount* (`./:/var/www/dev_laravel_project`). Setiap perubahan kode PHP atau Blade yang Anda lakukan di VS Code laptop akan **langsung berubah secara real-time** di dalam container.

---

## 🛑 Menghentikan Proyek

- **Menghentikan Sementara:**
  ```bash
  docker compose --file compose.dev.yaml stop
  ```
- **Menghidupkan Kembali:**
  ```bash
  docker compose --file compose.dev.yaml up -d
  ```
- **Menghapus Container & Reset Database:**
  ```bash
  docker compose --file compose.dev.yaml down -v
  ```
