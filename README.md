# Inventory Assessment

Aplikasi Inventory Management System berbasis **Laravel 10+** dengan admin panel **Filament v4**, autentikasi API via **Sanctum**, komponen reaktif **Livewire**, dan styling **Tailwind CSS v4**. Seluruh environment dijalankan menggunakan **Docker Compose** (app/PHP-FPM, nginx, MySQL, dan worker queue), dan dilengkapi `Makefile` untuk mempermudah operasional sehari-hari.

## Daftar Isi

- [Prerequisite](#prerequisite)
  - [Windows](#windows)
  - [macOS](#macos)
  - [Linux](#linux)
- [Instalasi Cepat (Otomatis)](#instalasi-cepat-otomatis)
- [Instalasi Manual (Step by Step)](#instalasi-manual-step-by-step)
- [Perintah Makefile yang Tersedia](#perintah-makefile-yang-tersedia)
- [Struktur Service Docker](#struktur-service-docker)
- [Fitur yang Telah Diterapkan](#fitur-yang-telah-diterapkan)
- [Teknologi & Tools yang Digunakan](#teknologi--tools-yang-digunakan)
- [Query Optimization](#query-optimization)
- [Troubleshooting](#troubleshooting)

## Prerequisite

Semua OS membutuhkan tiga hal dasar:

1. **Docker** & **Docker Compose** (v2, sudah terintegrasi sebagai `docker compose`, bukan `docker-compose`)
2. **Git** (atau **GitButler** untuk version control modern)
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

3. **Git for Windows** atau **GitButler**
   - Git: Unduh dari [git-scm.com](https://git-scm.com/download/win)
   - GitButler: Unduh dari [gitbutler.com](https://gitbutler.com/) (opsional, untuk version control modern)

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

2. **Git** atau **GitButler**
   - Git biasanya sudah terpasang bawaan macOS. Cek dengan:

     ```bash
     git --version
     ```

   - Jika belum ada, install via [Homebrew](https://brew.sh/):

     ```bash
     brew install git
     ```

   - GitButler: Unduh dari [gitbutler.com](https://gitbutler.com/) (opsional)

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

Admin panel Filament:

```
http://localhost:8000/admin
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


## Teknologi & Tools yang Digunakan

### Backend
- **Laravel 10+** - PHP Framework
- **PHP 8.2+** - Runtime
- **MySQL 8.0** - Database
- **Filament v4** - Admin Panel Framework
- **Laravel Sanctum** - API Authentication
- **Livewire** - Full-stack framework untuk dynamic UI
- **Queue Worker** - Background job processing

### Frontend
- **Tailwind CSS v4** - Utility-first CSS framework
- **Alpine.js** - Lightweight JavaScript framework
- **Vite** - Build tool & asset bundler

### DevOps & Infrastructure
- **Docker** - Containerization
- **Docker Compose v2** - Multi-container orchestration
- **nginx** - Web server / reverse proxy
- **PHP-FPM** - FastCGI Process Manager

### Development Tools
- **GitButler** - Version control (modern Git client)
- **GNU Make** - Build automation (via Makefile)
- **Composer** - PHP dependency manager
- **npm** - Node.js package manager


### Version Control

Project ini menggunakan **GitButler** untuk version control. GitButler menyediakan interface modern untuk Git dengan fitur seperti:
- Virtual branches
- Stacked changes
- Integrated diff viewer
- Automatic conflict resolution

## Section D: Database Performance at Scale

### Index Design

Untuk mengoptimalkan performa query pada dataset skala besar (1.2M rows), telah ditambahkan indexes khusus melalui migration `2026_07_26_222700_add_performance_indexes_to_stock_movements_table.php`.

#### Pattern 1: Warehouse + Date Range (Dashboard Widget)
**Query:**
```sql
SELECT sm.*, p.name AS product_name
FROM stock_movements sm
JOIN products p ON sm.product_id = p.id
WHERE sm.warehouse_id = 7
  AND sm.created_at BETWEEN '2026-05-01' AND '2026-06-01'
ORDER BY sm.created_at DESC
LIMIT 20;
```

**Index:** `idx_warehouse_created_at` (composite index pada `warehouse_id` dan `created_at`)

**Why:** Composite index ini memungkinkan database untuk:
- Filter rows berdasarkan warehouse_id secara efisien
- Kemudian filter berdasarkan date range pada created_at
- Menghindari full table scan pada 1.2M rows
- Expected improvement: 2.5s → ≤50ms

#### Pattern 2: Product Aggregate (Stock Report)
**Query:**
```sql
SELECT sm.movement_type, SUM(sm.quantity) AS total_quantity
FROM stock_movements sm
WHERE sm.product_id = 3421
  AND sm.movement_type = 'out';
```

**Index:** `idx_product_movement_type` (composite index pada `product_id` dan `movement_type`)

**Why:** Composite index ini memungkinkan:
- Filter rows berdasarkan product_id
- Kemudian filter berdasarkan movement_type
- Optimasi operasi agregasi SUM() pada subset yang sudah terfilter
- Expected improvement: 1.8s → ≤30ms

#### Pattern 3: Reference Lookup (Audit Trail)
**Query:**
```sql
SELECT sm.*, p.sku, p.name, w.name AS warehouse_name
FROM stock_movements sm
JOIN products p ON sm.product_id = p.id
JOIN warehouses w ON sm.warehouse_id = w.id
WHERE sm.reference_number = 'PO-2026-0158';
```

**Index:** `idx_reference_number` (single index pada `reference_number`)

**Why:** Index ini memungkinkan:
- Exact lookup berdasarkan reference_number
- Menghindari full table scan untuk mencari reference spesifik
- Expected improvement: 3.2s → ≤20ms

### Running the Migration

```bash
make migrate
```

Atau jika menggunakan Docker:
```bash
docker compose exec app php artisan migrate
```

### Production Data Seeding

Untuk testing performa dengan data skala produksi:

```bash
make seed-production
```

Perintah ini akan menjalankan `sql-seed-data/generate_production_data.sql` yang menghasilkan:
- 50 warehouses
- 5,000 products
- ~1,200,000 stock movements

Proses ini memakan waktu 5-10 menit.

### Question 2: Complex Report Query

#### Optimized SQL Query
Untuk mendapatkan report per warehouse (name, total distinct products, total stock value, most recently moved product), telah dibuat query menggunakan CTEs dan window functions:

```sql
WITH warehouse_stock AS (
    SELECT 
        pw.warehouse_id,
        COUNT(DISTINCT pw.product_id) as total_products,
        SUM(p.unit_price * pw.quantity_on_hand) as total_stock_value
    FROM product_warehouse pw
    INNER JOIN products p ON pw.product_id = p.id
    WHERE pw.quantity_on_hand > 0
    GROUP BY pw.warehouse_id
),
latest_movements AS (
    SELECT 
        sm.warehouse_id,
        sm.product_id,
        sm.created_at,
        ROW_NUMBER() OVER (PARTITION BY sm.warehouse_id ORDER BY sm.created_at DESC) as rn
    FROM stock_movements sm
)
SELECT 
    w.id,
    w.name,
    w.location,
    COALESCE(ws.total_products, 0) as total_distinct_products,
    COALESCE(ws.total_stock_value, 0) as total_stock_value,
    p.name as most_recently_moved_product,
    lm.created_at as most_recent_movement_date
FROM warehouses w
LEFT JOIN warehouse_stock ws ON w.id = ws.warehouse_id
LEFT JOIN latest_movements lm ON w.id = lm.warehouse_id AND lm.rn = 1
LEFT JOIN products p ON lm.product_id = p.id
WHERE w.is_active = true
ORDER BY w.name;
```

#### Laravel Eloquent Equivalent
Query ini juga tersedia dalam bentuk Eloquent di `WarehouseReportController@eloquent()` untuk perbandingan.

#### API Endpoints
- `GET /api/v1/warehouse-report` - Optimized SQL version
- `GET /api/v1/warehouse-report/eloquent` - Eloquent version

#### Additional Indexes
Migration `2026_07_26_223100_add_warehouse_report_indexes.php` menambahkan:
- `idx_warehouse_id` pada `product_warehouse` untuk join efisien
- `idx_warehouse_created_at_latest` pada `stock_movements` untuk window function

### Question 3: Reporting Optimization

#### Problem
Endpoint `GET /api/v1/stock-report` timeout (>30s) pada dataset skala besar (1.2M rows).

#### Solution: Cached Aggregation
Dipilih pendekatan **Cached Aggregation** dengan event-based invalidation.

**Implementation:**
1. **Cache Table**: `warehouse_report_cache` menyimpan data pre-aggregated
2. **Cache Service**: `WarehouseReportCacheService` mengelola refresh cache
3. **Event-Based Invalidation**: Cache di-refresh otomatis saat stock movement baru dibuat
4. **Stale Detection**: Cache di-refresh jika lebih dari 1 jam tidak di-update

**Migration**: `2026_07_26_223200_create_warehouse_report_cache_table.php`

**Controller Update**: `StockReportController@index()` sekarang menggunakan cache

**Model Event**: `StockMovement` model memanggil cache refresh pada event `created`

#### Performance Improvement

| Metric | Before (Uncached) | After (Cached) |
|--------|-------------------|----------------|
| Query Time | >30s (timeout) | ~50ms |
| Database Load | High (aggregation on 1.2M rows) | Low (simple SELECT from cache) |
| Scalability | Poor (linear degradation) | Excellent (constant time) |

#### Trade-offs

**Advantages:**
- ✅ Significantly faster response time (30s → 50ms)
- ✅ Reduced database load during peak traffic
- ✅ Event-based invalidation ensures data consistency
- ✅ Automatic stale detection and refresh
- ✅ Easy to implement with Laravel's event system

**Disadvantages:**
- ❌ Slight data staleness (up to 1 hour if no stock movements)
- ❌ Additional storage for cache table
- ❌ Cache refresh overhead on stock movement creation
- ❌ Increased complexity in codebase

**Why This Approach Over Others:**

| Approach | Pros | Cons | Chosen? |
|----------|------|------|---------|
| Materialized View | Native DB feature, efficient refresh | MySQL doesn't support native materialized views | ❌ |
| Cached Aggregation | Laravel-native, flexible, event-driven | Slight staleness, extra storage | ✅ |
| Partitioned Table | Good for time-series data | Complex query rewriting, maintenance overhead | ❌ |

#### API Endpoints for Comparison
- `GET /api/v1/stock-report` - Cached version (recommended)
- `GET /api/v1/stock-report/uncached` - Uncached version (for benchmarking)

#### Cache Management Commands
Untuk manual cache management:
```php
// Refresh all cache
app(WarehouseReportCacheService::class)->refreshCache();

// Refresh specific warehouse
app(WarehouseReportCacheService::class)->refreshWarehouseCache($warehouseId);

// Clear all cache
app(WarehouseReportCacheService::class)->clearCache();
```

## Access Information

Setelah instalasi selesai:

- **Application URL**: http://localhost:8000
- **Admin Panel**: http://localhost:8000/admin
- **Default Admin User**: Dibuat saat menjalankan `make filament-user`
