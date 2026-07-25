-- ============================================================
-- Production-Scale Seed Data for Inventory Management System
-- 1M+ stock movements across 50 warehouses and 5,000 products
-- Optimized for MySQL 8.0+ / MariaDB 10.6+
-- ============================================================
-- Expected runtime: 5-10 minutes depending on hardware
-- Disk space: ~400MB (with indexes)
-- ============================================================
-- REVISI: lihat CHANGES.md untuk daftar lengkap perbaikan
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- STEP 1: CLEANUP (Drop existing data if re-running)
-- ============================================================
TRUNCATE TABLE product_warehouse;
TRUNCATE TABLE stock_movements;
TRUNCATE TABLE products;
TRUNCATE TABLE warehouses;

-- ============================================================
-- STEP 2: CONFIGURATION
-- ============================================================
SET @NUM_WAREHOUSES = 50;
SET @NUM_PRODUCTS = 5000;
SET @NUM_MOVEMENTS = 1200000;  -- 1.2M for margin
SET @BATCH_SIZE = 10000;       -- Per-batch insert size

-- ============================================================
-- STEP 3: GENERATE WAREHOUSES (50 locations)
-- ============================================================
-- Major Indonesian logistics hubs + international
INSERT INTO warehouses (name, location, capacity_m3, is_active, created_at, updated_at)
SELECT
    CONCAT('WH-', LPAD(seq, 3, '0'), ' ', 
           ELT(seq,
               'Jakarta Utara', 'Jakarta Selatan', 'Jakarta Timur', 'Jakarta Barat',
               'Tangerang', 'Bekasi', 'Depok', 'Bogor',
               'Bandung', 'Cimahi', 'Cirebon', 'Tasikmalaya',
               'Semarang', 'Solo', 'Yogyakarta', 'Purwokerto',
               'Surabaya', 'Malang', 'Sidoarjo', 'Gresik',
               'Medan', 'Palembang', 'Padang', 'Pekanbaru', 'Batam',
               'Denpasar', 'Mataram', 'Kupang',
               'Makassar', 'Manado', 'Palu', 'Kendari',
               'Balikpapan', 'Samarinda', 'Pontianak', 'Banjarmasin',
               'Singapore Hub', 'Kuala Lumpur Hub', 'Bangkok Hub',
               'Ho Chi Minh Hub', 'Manila Hub', 'Hong Kong Hub',
               'Tokyo Hub', 'Osaka Hub', 'Seoul Hub',
               'Shanghai Hub', 'Shenzhen Hub', 'Taipei Hub',
               'Sydney Hub', 'Melbourne Hub'
           )) AS name,
    ELT(seq,
        'DKI Jakarta', 'DKI Jakarta', 'DKI Jakarta', 'DKI Jakarta',
        'Banten', 'Jawa Barat', 'Jawa Barat', 'Jawa Barat',
        'Jawa Barat', 'Jawa Barat', 'Jawa Barat', 'Jawa Barat',
        'Jawa Tengah', 'Jawa Tengah', 'DI Yogyakarta', 'Jawa Tengah',
        'Jawa Timur', 'Jawa Timur', 'Jawa Timur', 'Jawa Timur',
        'Sumatera Utara', 'Sumatera Selatan', 'Sumatera Barat', 'Riau', 'Kepulauan Riau',
        'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur',
        'Sulawesi Selatan', 'Sulawesi Utara', 'Sulawesi Tengah', 'Sulawesi Tenggara',
        'Kalimantan Timur', 'Kalimantan Timur', 'Kalimantan Barat', 'Kalimantan Selatan',
        'Singapore', 'Malaysia', 'Thailand',
        'Vietnam', 'Philippines', 'Hong Kong',
        'Japan', 'Japan', 'South Korea',
        'China', 'China', 'Taiwan',
        'Australia', 'Australia'
    ) AS location,
    ROUND(1000 + RAND() * 49000, 2) AS capacity_m3,  -- 1,000 - 50,000 m3
    IF(RAND() < 0.9, 1, 0) AS is_active,  -- 90% active
    NOW() - INTERVAL FLOOR(RAND() * 730) DAY AS created_at,  -- Up to 2 years old
    NOW() AS updated_at
FROM (
    SELECT @row := @row + 1 AS seq
    FROM (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t1,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t2,
         (SELECT 0 UNION ALL SELECT 1) t3,
         (SELECT @row := 0) init
) AS numbers
WHERE seq <= @NUM_WAREHOUSES;

-- ============================================================
-- STEP 4: GENERATE PRODUCTS (5,000 SKUs)
-- ============================================================
INSERT INTO products (sku, name, description, unit_price, weight_kg, category, is_active, created_at, updated_at)
SELECT
    CONCAT(
        UPPER(ELT(FLOOR(1 + RAND() * 10),
            'RAW', 'FIN', 'PKG', 'SPR', 'ELEC',
            'MECH', 'CHEM', 'TEXT', 'FOOD', 'PHARMA'
        )),
        '-',
        LPAD(FLOOR(1000 + RAND() * 8999), 4, '0'),
        '-',
        UPPER(SUBSTRING(MD5(RAND()), 1, 4))
    ) AS sku,
    CONCAT(
        ELT(FLOOR(1 + RAND() * 15),
            'Industrial Grade ', 'Premium ', 'Standard ', 'Heavy Duty ',
            'Lightweight ', 'Commercial ', 'Medical Grade ', 'Food Grade ',
            'Automotive ', 'Marine ', 'Aerospace ', 'Consumer ',
            'Professional ', 'Entry Level ', 'Enterprise '
        ),
        ELT(FLOOR(1 + RAND() * 20),
            'Widget Assembly', 'Sensor Module', 'Control Board', 'Power Supply',
            'Hydraulic Valve', 'Pneumatic Cylinder', 'Conveyor Belt', 'Motor Controller',
            'Gearbox', 'Pump Assembly', 'Filter Element', 'Lubricant Oil',
            'Packaging Film', 'Label Printer', 'Barcode Scanner', 'RFID Reader',
            'Forklift Attachment', 'Pallet Jack', 'Shrink Wrap Machine', 'Robotic Arm'
        ),
        ' v',
        FLOOR(1 + RAND() * 9),
        '.',
        FLOOR(0 + RAND() * 9)
    ) AS name,
    CONCAT(
        ELT(FLOOR(1 + RAND() * 5),
            'High-performance component for industrial applications. ',
            'Compatible with standard ISO specifications. ',
            'Manufactured under strict quality control standards. ',
            'Optimized for Southeast Asian climate conditions. ',
            'Includes 12-month warranty and technical support. '
        ),
        'Minimum order: ', FLOOR(10 + RAND() * 90), ' units. ',
        'Lead time: ', FLOOR(1 + RAND() * 14), ' days.'
    ) AS description,
    ROUND(0.50 + RAND() * 2499.50, 2) AS unit_price,  -- $0.50 - $2,500
    ROUND(0.01 + RAND() * 499.99, 2) AS weight_kg,   -- 0.01 - 500 kg
    ELT(FLOOR(1 + RAND() * 4), 'raw_material', 'finished_goods', 'packaging', 'spare_part') AS category,
    IF(RAND() < 0.95, 1, 0) AS is_active,  -- 95% active
    NOW() - INTERVAL FLOOR(RAND() * 730) DAY AS created_at,
    NOW() AS updated_at
FROM (
    SELECT @p_row := @p_row + 1 AS p_seq
    FROM (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t1,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t2,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t3,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t4,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t5,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t6,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t7,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t8,
         (SELECT 0 UNION ALL SELECT 1) t9,
         (SELECT @p_row := 0) init
) AS numbers
WHERE p_seq <= @NUM_PRODUCTS;

-- ============================================================
-- STEP 5: GENERATE PRODUCT_WAREHOUSE STOCK LEVELS
-- Each product exists in 1-5 random warehouses
-- [DIPERBAIKI] Query lama pakai correlated subquery ilegal di FROM
-- (referensi p.id dari luar ke dalam derived table), yang membuat
-- MySQL error "Unknown column 'p.id' in field list". Sekarang
-- jumlah warehouse per produk (num_warehouses) dihitung sekali per
-- produk pakai FIRST_VALUE() supaya konsisten dalam satu partisi.
-- ============================================================
INSERT INTO product_warehouse (product_id, warehouse_id, quantity_on_hand, created_at, updated_at)
SELECT
    product_id,
    warehouse_id,
    FLOOR(RAND() * 1000) AS quantity_on_hand,  -- 0-999 units per warehouse
    NOW() - INTERVAL FLOOR(RAND() * 365) DAY AS created_at,
    NOW() AS updated_at
FROM (
    SELECT
        p.id AS product_id,
        w.id AS warehouse_id,
        ROW_NUMBER() OVER (PARTITION BY p.id ORDER BY RAND()) AS rn,
        FIRST_VALUE(FLOOR(1 + RAND() * 5))
            OVER (PARTITION BY p.id ORDER BY RAND()) AS num_warehouses
    FROM products p
    CROSS JOIN warehouses w
    WHERE p.id <= @NUM_PRODUCTS
) t
WHERE t.rn <= t.num_warehouses;

-- ============================================================
-- STEP 6: GENERATE 1.2M STOCK MOVEMENTS (Batch Insert)
-- ============================================================
-- This uses a stored procedure for efficient batch insertion
-- [DIPERBAIKI] DROP PROCEDURE ditambahkan sebelum CREATE supaya
-- perubahan isi prosedur selalu kepakai saat script dijalankan ulang
-- (sebelumnya pakai "CREATE PROCEDURE IF NOT EXISTS" yang membuat
-- MySQL skip pembuatan ulang kalau prosedur lama masih ada).
-- ============================================================
DROP PROCEDURE IF EXISTS GenerateStockMovements;
DELIMITER //
CREATE PROCEDURE GenerateStockMovements()
BEGIN
    DECLARE batch_count INT DEFAULT 0;
    DECLARE total_batches INT DEFAULT CEIL(@NUM_MOVEMENTS / @BATCH_SIZE);

    WHILE batch_count < total_batches DO
        -- [DIPERBAIKI] movement_type sebelumnya dipanggil ulang lewat
        -- ELT(FLOOR(1 + RAND()*4), ...) di 3 tempat berbeda (kolom
        -- movement_type + 2x di dalam CASE quantity), yang masing-masing
        -- menghasilkan angka random SENDIRI-SENDIRI. Akibatnya quantity
        -- bisa tidak nyambung dengan movement_type (misal movement_type
        -- 'in' tapi quantity negatif). Sekarang movement_type dihitung
        -- SEKALI di subquery "base", baru dipakai ulang di CASE quantity.
        INSERT INTO stock_movements (
            product_id, warehouse_id, movement_type, quantity,
            reference_number, notes, moved_by, created_at, updated_at
        )
        SELECT
            product_id,
            warehouse_id,
            movement_type,
            CASE movement_type
                WHEN 'in' THEN FLOOR(1 + RAND() * 500)
                WHEN 'out' THEN -FLOOR(1 + RAND() * 500)
                ELSE FLOOR(-100 + RAND() * 201)  -- transfer/adjustment: -100 to +100
            END AS quantity,
            CONCAT(
                ELT(FLOOR(1 + RAND() * 4), 'PO', 'SO', 'TR', 'ADJ'),
                '-202',
                FLOOR(3 + RAND() * 4),
                '-',
                LPAD(FLOOR(1 + RAND() * 9999), 4, '0')
            ) AS reference_number,
            CONCAT(
                ELT(FLOOR(1 + RAND() * 5),
                    'Regular procurement from supplier.',
                    'Customer order fulfillment.',
                    'Inter-warehouse transfer for stock balancing.',
                    'Inventory adjustment after cycle count.',
                    'Damaged goods write-off per QA report.'
                ),
                ' Processed by system automation. Batch #', FLOOR(1000 + RAND() * 8999), '.'
            ) AS notes,
            CONCAT(
                LOWER(SUBSTRING(MD5(RAND()), 1, 8)),
                '@logisteed.com'
            ) AS moved_by,
            NOW() - INTERVAL FLOOR(RAND() * 365 * 2) DAY AS created_at,  -- Up to 2 years back
            NOW() AS updated_at
        FROM (
            SELECT
                FLOOR(1 + RAND() * @NUM_PRODUCTS) AS product_id,
                FLOOR(1 + RAND() * @NUM_WAREHOUSES) AS warehouse_id,
                ELT(FLOOR(1 + RAND() * 4), 'in', 'out', 'transfer', 'adjustment') AS movement_type
            FROM (
                SELECT @m_row := @m_row + 1 AS m_seq
                FROM (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t1,
                     (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t2,
                     (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t3,
                     (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t4,
                     (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t5,
                     (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t6,
                     (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t7,
                     (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t8,
                     (SELECT 0 UNION ALL SELECT 1) t9,
                     (SELECT @m_row := 0) init
            ) AS numbers
            WHERE m_seq <= @BATCH_SIZE
        ) AS base;

        SET batch_count = batch_count + 1;

        -- Progress indicator every 100K rows
        IF batch_count % 10 = 0 THEN
            SELECT CONCAT('Inserted ', batch_count * @BATCH_SIZE, ' movements (', 
                         ROUND(batch_count * 100 / total_batches, 1), '% complete)') AS progress;
        END IF;
    END WHILE;
END //
DELIMITER ;

-- Execute the stored procedure
CALL GenerateStockMovements();

-- [DIPERBAIKI] Bersihkan prosedur setelah dipakai supaya tidak
-- menumpuk sebagai objek permanen di database (prosedur ini hanya
-- untuk keperluan seed sekali pakai, bukan bagian dari skema app).
DROP PROCEDURE IF EXISTS GenerateStockMovements;

-- ============================================================
-- STEP 7: VERIFY DATA INTEGRITY
-- ============================================================
SELECT 'WAREHOUSES' AS table_name, COUNT(*) AS row_count FROM warehouses
UNION ALL
SELECT 'PRODUCTS', COUNT(*) FROM products
UNION ALL
SELECT 'PRODUCT_WAREHOUSE', COUNT(*) FROM product_warehouse
UNION ALL
SELECT 'STOCK_MOVEMENTS', COUNT(*) FROM stock_movements;

-- Check date ranges
SELECT 
    'stock_movements' AS table_name,
    MIN(created_at) AS earliest,
    MAX(created_at) AS latest,
    COUNT(DISTINCT DATE(created_at)) AS distinct_days
FROM stock_movements;

-- Check distribution by movement type
SELECT movement_type, COUNT(*) AS count, ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) AS pct
FROM stock_movements
GROUP BY movement_type;

-- Verify foreign key consistency (should be 0 violations)
SELECT COUNT(*) AS fk_violations
FROM stock_movements sm
LEFT JOIN products p ON sm.product_id = p.id
LEFT JOIN warehouses w ON sm.warehouse_id = w.id
WHERE p.id IS NULL OR w.id IS NULL;

-- [DIPERBAIKI] Ditambahkan: verifikasi bahwa quantity selalu selaras
-- dengan movement_type (harusnya 0 baris setelah perbaikan Step 6).
SELECT COUNT(*) AS movement_type_quantity_mismatch
FROM stock_movements
WHERE (movement_type = 'in' AND quantity <= 0)
   OR (movement_type = 'out' AND quantity >= 0);

-- ============================================================
-- STEP 8: ANALYZE TABLES FOR QUERY OPTIMIZER
-- ============================================================
ANALYZE TABLE warehouses;
ANALYZE TABLE products;
ANALYZE TABLE product_warehouse;
ANALYZE TABLE stock_movements;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- NOTES FOR CANDIDATE / INTERVIEWER
-- ============================================================
-- 
-- Run this SQL file BEFORE the interview test:
--   mysql -u root -p inventory_test < generate_production_data.sql
--
-- Or from MySQL client:
--   SOURCE /path/to/generate_production_data.sql;
--
-- Expected output after completion:
--   +-------------------+-----------+
--   | table_name        | row_count |
--   +-------------------+-----------+
--   | WAREHOUSES        | 50        |
--   | PRODUCTS          | 5000      |
--   | PRODUCT_WAREHOUSE | ~15000    |
--   | STOCK_MOVEMENTS   | 1200000   |
--   +-------------------+-----------+
--
-- This gives the candidate realistic volume to optimize against.