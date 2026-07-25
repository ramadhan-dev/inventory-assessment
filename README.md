# Inventory Assessment

Aplikasi Inventory Assessment berbasis **Laravel** dengan admin panel **Filament**, autentikasi API via **Sanctum**, komponen reaktif **Livewire**, dan styling **Tailwind CSS v4**. Seluruh environment dijalankan menggunakan **Docker Compose** (app/PHP-FPM, nginx, MySQL, dan worker queue), dan dilengkapi `Makefile` untuk mempermudah operasional sehari-hari.

## Daftar Isi

- [Prerequisite](#prerequisite)
  - [Windows](#windows)
  - [macOS](#macos)
  - [Linux](#linux)
- [Instalasi Cepat (Otomatis)](#instalasi-cepat-otomatis)
- [Instalasi Manual (Step by Step)](#instalasi-manual-step-by-step)
- [Perintah Makefile yang Tersedia](#perintah-makefile-yang-tersedia)
- [Struktur Service Docker](#struktur-service-docker)
- [Troubleshooting](#troubleshooting)

## Prerequisite

Semua OS membutuhkan tiga hal dasar:

1. **Docker** & **Docker Compose** (v2, sudah terintegrasi sebagai `docker compose`, bukan `docker-compose`)
2. **Git**
3. **GNU Make** (untuk menjalankan perintah-perintah di `Makefile`)

Detail instalasi per OS ada di bawah ini.

### Windows

1. **WSL2 (Windows Subsystem for Linux)** — wajib sebagai backend Docker Desktop.

   ```powershell
   wsl --install
   ```

   Restart komputer setelah proses ini selesai.

2. **Docker Desktop for Windows**
   - Unduh dari [docker.com](https://www.docker.com/products/docker-desktop/)
   - Saat instalasi, pastikan opsi **"Use WSL 2 based engine"** dicentang.
   - Setelah terinstal, buka **Settings > Resources > WSL Integration** dan aktifkan integrasi untuk distro WSL yang dipakai (misalnya Ubuntu).

3. **Git for Windows**
   - Unduh dari [git-scm.com](https://git-scm.com/download/win)

4. **GNU Make**
   Windows tidak menyertakan `make` secara default. Pilih salah satu cara:
   - **Disarankan:** jalankan semua perintah di dalam **WSL2/Ubuntu**, lalu install make di dalamnya:

     ```bash
     sudo apt update && sudo apt install -y make
     ```

   - Atau via **Chocolatey** (di PowerShell sebagai Administrator):

     ```powershell
     choco install make
     ```

   - Atau via **Scoop**:

     ```powershell
     scoop install make
     ```

> **Catatan:** Untuk pengalaman paling stabil di Windows, disarankan clone repo dan menjalankan seluruh perintah `make` dari dalam terminal WSL2 (bukan PowerShell/CMD langsung), karena Docker volume mount dan permission Linux lebih konsisten di sana.

### macOS

1. **Docker Desktop for Mac**
   - Unduh dari [docker.com](https://www.docker.com/products/docker-desktop/) (pilih chip Apple Silicon atau Intel sesuai perangkat).
   - Docker Compose v2 sudah otomatis tersedia sebagai bagian dari Docker Desktop.

2. **Git**
   - Biasanya sudah terpasang bawaan macOS. Cek dengan:

     ```bash
     git --version
     ```

   - Jika belum ada, install via [Homebrew](https://brew.sh/):

     ```bash
     brew install git
     ```

3. **GNU Make**
   - macOS sudah menyertakan `make` bawaan (via Xcode Command Line Tools). Jika belum ada:

     ```bash
     xcode-select --install
     ```

   - Atau via Homebrew untuk versi GNU Make terbaru:

     ```bash
     brew install make
     ```

### Linux

Contoh untuk **Ubuntu/Debian**:

1. **Docker Engine + Docker Compose plugin**

   ```bash
   sudo apt update
   sudo apt install -y ca-certificates curl gnupg
   sudo install -m 0755 -d /etc/apt/keyrings
   curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
   sudo chmod a+r /etc/apt/keyrings/docker.gpg

   echo \
     "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
     $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
     sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

   sudo apt update
   sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
   ```

2. **Jalankan Docker tanpa `sudo` (opsional tapi disarankan)**

   ```bash
   sudo usermod -aG docker $USER
   newgrp docker
   ```

3. **Git & Make**

   ```bash
   sudo apt install -y git make
   ```

   Untuk distro berbasis RHEL/Fedora, ganti `apt` dengan `dnf`, dan ikuti [panduan resmi Docker untuk Fedora/CentOS](https://docs.docker.com/engine/install/).

## Instalasi Cepat (Otomatis)

Jika seluruh prerequisite di atas sudah terpasang, project ini menyediakan satu perintah untuk setup lengkap dari nol ("skenario laptop baru"):

```bash
git clone <url-repo-anda>
cd <nama-folder-project>
make fresh-install
```

Perintah `fresh-install` akan otomatis menjalankan urutan berikut:

1. Menyalin `.env.example` menjadi `.env`
2. Build image & menjalankan seluruh container (`up-build`)
3. Menunggu MySQL siap (`wait-db`)
4. `composer install`
5. Generate application key (`key`)
6. Perbaikan permission storage (`permission`)
7. Migrasi database (`migrate`)
8. Install & setup Filament admin panel (`filament-install`)
9. Install Sanctum untuk API auth (`sanctum-install`)
10. Install Livewire (`livewire-install`)
11. Install Tailwind CSS v4 & Alpine.js (`tailwind-install`)
12. Build asset frontend (`npm-build`)
13. Seeding data demo (`seed`)
14. Publish asset Filament (`filament-assets`)
15. Membuat user admin Filament (`filament-user`) — akan meminta input interaktif (nama, email, password)
16. Seeding data performa besar (`seed-production`) — proses ini bisa memakan waktu 5–10 menit

Setelah selesai, aplikasi bisa diakses di:

```
http://localhost:8000
```

## Instalasi Manual (Step by Step)

Jika ingin mengontrol setiap langkah secara manual (misalnya untuk debugging), berikut urutan yang setara dengan `fresh-install`:

```bash
# 1. Siapkan file environment
cp .env.example .env

# 2. Build & jalankan container
make up-build

# 3. Tunggu MySQL siap
make wait-db

# 4. Install dependency PHP
make install

# 5. Generate application key
make key

# 6. Perbaiki permission storage & cache
make permission

# 7. Jalankan migrasi database
make migrate

# 8. Install admin panel Filament
make filament-install

# 9. Install Sanctum (API authentication)
make sanctum-install

# 10. Install Livewire
make livewire-install

# 11. Install Tailwind CSS v4 + Alpine.js
make tailwind-install

# 12. Build asset frontend untuk production
make npm-build

# 13. Jalankan seeder data demo
make seed

# 14. Publish asset Filament
make filament-assets

# 15. Buat user admin untuk login Filament
make filament-user

# 16. (Opsional) Seed data performa besar untuk testing
make seed-production
```

## Perintah Makefile yang Tersedia

Lihat daftar lengkap kapan saja dengan:

```bash
make help
```

| Kategori | Perintah | Keterangan |
|---|---|---|
| **Lifecycle** | `make build` | Build ulang image tanpa cache |
| | `make up` | Jalankan semua container (background) |
| | `make up-build` | Build lalu jalankan semua container |
| | `make down` | Hentikan & hapus container (volume tetap ada) |
| | `make restart` | Restart semua container |
| | `make ps` | Lihat status container |
| **Logs** | `make logs` | Tail log semua service |
| | `make logs-app` | Tail log PHP-FPM |
| | `make logs-nginx` | Tail log nginx |
| | `make logs-mysql` | Tail log MySQL |
| | `make logs-worker` | Tail log queue worker |
| **Shell Access** | `make sh` | Masuk shell container app |
| | `make sh-nginx` | Masuk shell container nginx |
| | `make sh-mysql` | Masuk shell container MySQL |
| **Laravel & Composer** | `make install` | `composer install` |
| | `make key` | Generate application key |
| | `make migrate` | Jalankan migration |
| | `make migrate-fresh` | Reset DB lalu migrate ulang (⚠️ data hilang) |
| | `make make-migration name=xxx` | Buat file migration baru |
| | `make seed` | Jalankan database seeder |
| | `make cache-clear` | Bersihkan semua cache Laravel |
| | `make permission` | Perbaiki permission storage & bootstrap/cache |
| **Filament** | `make filament-install` | Install & setup panel Filament |
| | `make filament-user` | Buat user admin Filament |
| | `make filament-assets` | Publish/refresh asset Filament |
| **Auth & Frontend** | `make sanctum-install` | Install Sanctum untuk API auth |
| | `make livewire-install` | Install Livewire |
| | `make tailwind-install` | Install Tailwind CSS v4 + Alpine.js |
| | `make npm-dev` | Compile asset (watch mode/dev) |
| | `make npm-build` | Compile asset untuk production |
| **Queue** | `make queue-restart` | Restart queue worker |
| **Database** | `make wait-db` | Tunggu MySQL siap menerima koneksi |
| | `make seed-production` | Seed ~1.2 juta baris untuk testing performa |
| **Cleanup** | `make clean` | Hentikan container & hapus volume (DB ikut terhapus) |
| | `make nuke` | Bersih total: container, volume, image lokal, build cache |
| **Kombinasi** | `make fresh-install` | Setup lengkap dari nol |
| | `make reset` | Reset total lalu setup ulang dari awal |

## Struktur Service Docker

Berdasarkan `Makefile`, project ini terdiri dari service berikut (didefinisikan di `docker-compose.yml`):

- **app** — PHP-FPM, menjalankan aplikasi Laravel
- **nginx** — web server / reverse proxy
- **mysql** — database MySQL
- **worker** — queue worker Laravel

## Troubleshooting

- **`make: command not found`** → Pastikan GNU Make sudah terinstal sesuai panduan [Prerequisite](#prerequisite) untuk OS Anda.
- **Container MySQL belum siap saat migrate** → Jalankan `make wait-db` sebelum `make migrate`.
- **Permission error di `storage/` atau `bootstrap/cache/`** → Jalankan `make permission`.
- **Ingin mulai dari kondisi bersih total** → Jalankan `make nuke` lalu `make fresh-install`.
- **Port bentrok (misalnya 8000 sudah dipakai)** → Sesuaikan port di `docker-compose.yml`, lalu jalankan ulang `make up-build`.
