# Technical Assessment — Full-Stack Developer (PHP / Laravel / Filament)

> **Assessment Type:** Take-home assignment (unsupervised, asynchronous)  
> **Time Limit:** 4 Days (honor system — please track your time)  
> **Submission Deadline:** See email invitation  
> **Submission Method:** GitHub / GitLab repository link + README (Set as Public)

---

## What You're Building

A simplified **Inventory Management System** for a logistics company. The system tracks:

- **Warehouses** — storage locations with capacity
- **Products** — SKUs with categories, pricing, and weight
- **Stock Movements** — every in, out, transfer, and adjustment

You'll build this as a **Laravel 10+ application with Filament v4 admin panel**, expose a **REST API**, create a **Livewire + Alpine.js frontend component**, and optimize for **production-scale data**.

---

## Project Structure (What We Expect)

```
inventory-assessment/
├── README.md                          ← START HERE — your guide
├── .env.example                       ← Environment template
├── app/
│   ├── Models/                        ← Warehouse, Product, StockMovement
│   ├── Filament/Resources/            ← 3 admin resources + widgets
│   ├── Http/Controllers/API/          ← REST API controllers
│   ├── Http/Requests/API/             ← Form Request validation
│   ├── Livewire/                      ← StockAdjustmentManager
│   └── ...
├── database/
│   ├── migrations/                    ← All tables + indexes
│   └── seeders/                       ← Factory-based seeders (200 rows)
├── resources/
│   └── views/livewire/                ← Blade components
├── routes/
│   ├── api.php                        ← v1 API routes
│   └── web.php                        ← Livewire page route
└── tests/
    └── Feature/                       ← Minimum 5 feature tests
```

---

## Time Allocation Guide

| Section | Task | Suggested Time |
|---------|------|---------------|
| A | Laravel + Filament CRUD | 90 min |
| B | REST API + Integration | 60 min |
| C | Livewire + Alpine.js | 60 min |
| D | Database & SQL (Performance) | 45 min |
| E | DevOps & Deployment | 30 min |
| F | Troubleshooting Written Answers | 30 min |
| — | Polish README + commit | 25 min |
| **Total** | | **~4 hours** |

> **Tip:** If stuck on one section > 30 min, move on and return later. Partial completion is better than one perfect section.

---

## Getting Started

### 1. Project Setup (Start from Scratch)

**Required approach:** Scaffold your own Laravel project from scratch. No starter repository provided.

```bash
# Step 1: Create Laravel project
laravel new inventory-assessment --no-interaction
cd inventory-assessment

# Step 2: Install Filament v4
composer require filament/filament:"^4.0" --with-all-dependencies

# Step 3: Install Sanctum (API auth)
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Step 4: Install Livewire
composer require livewire/livewire

# Step 5: Configure Tailwind CSS
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

**Configure Tailwind CSS:**

Create/replace `tailwind.config.js`:
```javascript
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./app/Filament/**/*.php",
        "./app/Livewire/**/*.php",
        "./vendor/filament/**/*.blade.php",
        "./vendor/livewire/livewire/dist/**/*.js",
    ],
    theme: {
        extend: {},
    },
    plugins: [],
}
```

Create/replace `resources/css/app.css`:
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

Replace `resources/js/app.js`:
```javascript
import './bootstrap';
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
```

Install JS dependencies:
```bash
npm install alpinejs
npm run build
```

**Step 6: Configure Pest / PHPUnit**

```bash
# Pest is pre-installed with Laravel 10+, just configure it
# Create pest.php if it doesn't exist
```

Create `tests/Pest.php` (if not present):
```php
<?php

uses(\Tests\TestCase::class)->in('Feature');
```

Create `tests/TestCase.php` (standard Laravel):
```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;
}
```

**Step 7: Initialize Filament Panel**
```bash
php artisan filament:install --panels
```

### 2. Base Database Schema

**Create these migrations first.** They must match the production seed script structure exactly (Section D requires this).

#### Migration 1: Create Warehouses Table
```php
<?php
// database/migrations/xxxx_xx_xx_000001_create_warehouses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('location', 255);
            $table->decimal('capacity_m3', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
```

#### Migration 2: Create Products Table
```php
<?php
// database/migrations/xxxx_xx_xx_000002_create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 100)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('weight_kg', 10, 2);
            $table->enum('category', ['raw_material', 'finished_goods', 'packaging', 'spare_part']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

#### Migration 3: Create Product Warehouse Pivot Table
```php
<?php
// database/migrations/xxxx_xx_xx_000003_create_product_warehouse_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_warehouse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->integer('quantity_on_hand')->default(0);
            $table->timestamps();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_warehouse');
    }
};
```

#### Migration 4: Create Stock Movements Table
```php
<?php
// database/migrations/xxxx_xx_xx_000004_create_stock_movements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->enum('movement_type', ['in', 'out', 'transfer', 'adjustment']);
            $table->integer('quantity'); // positive for in, negative for out
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('moved_by', 255);
            $table->timestamps();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
```

> **IMPORTANT:** These migrations must match the production seed script column names/types exactly, or Section D injection will fail. You may also add indexes as you see necessary.

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
# Edit .env with your database credentials
```

### 2. Environment Setup

```bash
# Install dependencies
composer install
npm install && npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env with your local database credentials
# DB_DATABASE=inventory_assessment

# Run migrations (creates empty tables)
php artisan migrate

# Verify installation
php artisan serve
# Visit http://localhost:8000/admin — login with provided demo credentials
```

### 3. Seed Small Dataset (for Development)

```bash
# Seed 50 products, 5 warehouses, 200 stock movements (for dev/testing)
php artisan db:seed --class=DemoSeeder
```

> This small dataset is ONLY for development convenience. **Section D requires 1.2M rows** — see below.

---

## Section A: Laravel + Filament CRUD (30 pts)

### What to Build

Complete the Inventory Management System with three Filament resources:

#### 1. Warehouse Resource
- List: searchable, filter by `is_active`, sort by `capacity_m3`
- Form: validation (name: 3-100 chars, capacity: numeric > 0)
- View page: show related products with current stock quantities

#### 2. Product Resource
- List: global search on SKU/name, filters by category and `is_active`
- Form: SKU validation (uppercase, alphanumeric, dashes, **immutable after creation**)
- Relationship manager: warehouses with editable `quantity_on_hand` pivot

#### 3. StockMovement Resource
- List: filters for date range, movement_type, warehouse
- Export action: CSV export for filtered results
- Stats widget: total movements today, inbound vs outbound quantities

#### 4. Dashboard Widgets
- Total active products count (large stat)
- Top 5 warehouses by capacity utilization %
- Recent stock movements table (last 24 hours)

### Business Rules (Must Enforce)

| Rule | Where | Behavior |
|------|-------|----------|
| BR1: SKU unique + immutable | Product form / model | Cannot change SKU after creation; validate uniqueness |
| BR2: No deactivate with stock | Warehouse edit | Reject if `product_warehouse` has `quantity_on_hand > 0` |
| BR3: Transfer qty ≤ available | StockMovement form | Validate source warehouse has sufficient stock |
| BR4: Unit price ≥ 0 | Product form | Numeric, minimum 0 |
| BR5: Movement qty ≠ 0 | StockMovement form | Integer, cannot be zero |

### Deliverables Checklist

- [ ] `app/Models/Warehouse.php` — relations, casts, scopes
- [ ] `app/Models/Product.php` — relations, casts, SKU mutator
- [ ] `app/Models/StockMovement.php` — relations, movement validation
- [ ] `app/Filament/Resources/WarehouseResource.php`
- [ ] `app/Filament/Resources/ProductResource.php`
- [ ] `app/Filament/Resources/StockMovementResource.php`
- [ ] `app/Filament/Widgets/*` — dashboard widgets (3 minimum)
- [ ] `database/migrations/*` — all tables with proper types
- [ ] `database/factories/*` — factories for testing
- [ ] `tests/Feature/*` — minimum 5 tests covering business rules
- [ ] create **Section A** in `README.md` to add any necessary explanation

---

## Section B: REST API + Integration (25 pts)

### API Endpoints to Implement

All routes under `/api/v1/` with **Sanctum token authentication**.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/products` | Paginated list; filter: `category`, `search`, `is_active` |
| GET | `/api/v1/products/{sku}` | Single product with warehouse stock levels |
| POST | `/api/v1/stock-movements` | Record new stock movement |
| GET | `/api/v1/warehouses/{id}/stock` | All products + quantities in warehouse |
| GET | `/api/v1/stock-report` | Aggregated value per warehouse |

### Authentication Requirements

- Token-based (Laravel Sanctum)
- Rate limit: 60 requests/minute per token
- Token generation via Filament admin panel (ApiToken resource)

### Request/Response Example

**POST /api/v1/stock-movements**

```json
// Request Headers:
// Authorization: Bearer {your-token}
// Content-Type: application/json

{
  "product_sku": "LAPTOP-DELL-XPS13",
  "warehouse_id": 3,
  "movement_type": "in",
  "quantity": 50,
  "reference_number": "PO-2026-0158",
  "notes": "Procurement from Jakarta supplier",
  "moved_by": "andi.permadi"
}

// Response 201 Created:
{
  "success": true,
  "data": {
    "id": 1042,
    "product": { "id": 15, "sku": "LAPTOP-DELL-XPS13", "name": "Dell XPS 13" },
    "warehouse": { "id": 3, "name": "Warehouse Cikarang" },
    "movement_type": "in",
    "quantity": 50,
    "reference_number": "PO-2026-0158",
    "created_at": "2026-06-22T10:30:00+07:00"
  }
}
```

### Integration Client Script

Create a standalone PHP script (not part of the web app) that:

1. Obtains API token (or accepts one as argument)
2. Fetches all products where `category = "finished_goods"`
3. For each product, records a stock-out of 10 units to `warehouse_id = 1`
4. Handles errors gracefully:
   - Logs HTTP errors with timestamp
   - Retries once on 429 (rate limit) or 500 (server error) with exponential backoff
   - Continues with remaining products if one fails

### Deliverables Checklist

- [ ] `app/Http/Controllers/API/ProductController.php`
- [ ] `app/Http/Controllers/API/StockMovementController.php`
- [ ] `app/Http/Controllers/API/WarehouseController.php`
- [ ] `app/Http/Requests/API/*` — Form Request validation classes
- [ ] `app/Http/Resources/*` — API Resource transformers
- [ ] `routes/api.php` — versioned routes with middleware
- [ ] `scripts/integration_client.php` — standalone integration script
- [ ] Rate limiter configuration
- [ ] Create **Section B** in `README.md` to add any necessary explanation

---

## Section C: Livewire + Alpine.js Component (20 pts)

### What to Build

A **Stock Adjustment Page** — a reactive form for warehouse staff to adjust inventory without page reloads.

### UI Requirements

```
┌─────────────────────────────────────────┐
│  Stock Adjustment                       │
│                                         │
│  Product:   [Search...           ]      │
│  Warehouse: [Select warehouse    ]      │
│  Current Stock: 247 units               │
│                                         │
│  [-]  [____25____]  [+]                 │
│         ↑↑ Alpine.js +/- buttons        │
│                                         │
│  Reason:    [Textarea            ]      │
│                                         │
│  [Submit Adjustment]                    │
│  ↑ Loading spinner, disabled during     │
│                                         │
│  [Success] or [Error] toast             │
└─────────────────────────────────────────┘
```

### Technical Requirements

**Livewire (`app/Livewire/StockAdjustmentManager.php`):**
- `#[Validate]` attributes for server-side rules
- `#[On('product-selected')]` event listener
- `$this->dispatch('stock-adjusted')` after successful save
- Computed property: `$this->availableStock`

**Alpine.js (inside Blade template):**
- `+`/`-` buttons increment/decrement quantity
- Client-side validation: qty ≤ `current_stock`, qty ≥ 1
- Debounced input validation
- Loading state on submit (disable inputs, show spinner)
- Success/error toast notification

**Blade (`resources/views/livewire/stock-adjustment-manager.blade.php`):**
- Tailwind CSS styling
- Responsive layout
- Accessibility: proper labels, focus states, ARIA where appropriate

### Deliverables Checklist

- [ ] `app/Livewire/StockAdjustmentManager.php`
- [ ] `resources/views/livewire/stock-adjustment-manager.blade.php`
- [ ] Route in `routes/web.php` to render the component
- [ ] Brief wireframe comment explaining data flow
- [ ] Create **Section C** in `README.md` to add any necessary explanation

---

## Section D: Database Performance at Scale (15 pts)

> **Critical:** This section requires **1.2 million rows** in `stock_movements`.  
> Do NOT attempt with the 200-row demo seeder.

### Loading Production Data

```bash
# Download and run the production seed script
mysql -u YOUR_DB_USER -p inventory_assessment < sql-seed-data/generate_production_data.sql

# Verify:
mysql -u YOUR_DB_USER -p -e "SELECT COUNT(*) FROM stock_movements;" inventory_assessment
# Expected: ~1,200,000
```

> **Note:** This script creates 50 warehouses, 5,000 products, and 1.2M stock movements. It takes 5–10 minutes to run.

### Question 1: Index Design (5 pts)

The dashboard queries are slow. Analyze these real query patterns and add optimal indexes:

```sql
-- Pattern 1: Warehouse + date range (dashboard widget)
SELECT sm.*, p.name AS product_name
FROM stock_movements sm
JOIN products p ON sm.product_id = p.id
WHERE sm.warehouse_id = 7
  AND sm.created_at BETWEEN '2026-05-01' AND '2026-06-01'
ORDER BY sm.created_at DESC
LIMIT 20;

-- Pattern 2: Product aggregate (stock report)
SELECT sm.movement_type, SUM(sm.quantity) AS total_quantity
FROM stock_movements sm
WHERE sm.product_id = 3421
  AND sm.movement_type = 'out';

-- Pattern 3: Reference lookup (audit trail)
SELECT sm.*, p.sku, p.name, w.name AS warehouse_name
FROM stock_movements sm
JOIN products p ON sm.product_id = p.id
JOIN warehouses w ON sm.warehouse_id = w.id
WHERE sm.reference_number = 'PO-2026-0158';
```

**Deliverable:**
- Migration file adding indexes
- Explanation of WHY each index helps
- `EXPLAIN` output before and after

**Targets:**

| Pattern | Before | After |
|---------|--------|-------|
| Warehouse + date | 2.5s | ≤50ms |
| Product aggregate | 1.8s | ≤30ms |
| Reference lookup | 3.2s | ≤20ms |

### Question 2: Complex Report Query (5 pts)

Write a SINGLE optimized query for:

> Each active warehouse: name, total distinct products in stock, total stock value (unit_price × quantity_on_hand), most recently moved product name + date.

**Must be efficient at 1.2M rows.**

Deliverable:
- Raw SQL
- Laravel Eloquent equivalent
- `EXPLAIN` showing no `ALL` scans

### Question 3: Reporting Optimization (5 pts)

`GET /api/v1/stock-report` times out (>30s) at scale.

**Pick ONE approach:**

**A. Materialized View / Summary Table** — Pre-aggregated daily data, refreshed via trigger or schedule  
**B. Cached Aggregation** — Laravel Cache with tags, warming strategy, invalidation on new movements  
**C. Partitioned Table** — Range partition `stock_movements` by month, rewrite query to prune partitions  

Deliverable:
- Implementation (migration + code)
- Before/after timing benchmark
- Trade-offs of chosen approach
- Create **Section D** in `README.md` to add any necessary explanation
---

## Section E: DevOps & Deployment (10 pts)

### Choose ONE Approach

**Option A: Traditional VPS**
Provide:
- Nginx vhost config (SSL redirect, gzip, PHP-FPM)
- PHP-FPM pool config (Laravel-optimized)
- `.env` production template
- Supervisor config for queue workers
- Scheduler cron entry
- Deployment script (zero-downtime)

**Option B: Docker**
Provide:
- `docker-compose.yml` (app, nginx, mysql, redis, worker)
- `Dockerfile` (multi-stage: composer → production image)
- `.env.docker` template

### Deliverable

Save configs to `deployment/` directory with README explaining setup steps or create **Section E** in `README.md` to add any necessary explanation

---

## Section F: Troubleshooting Written Responses (10 pts)

Answer in a text file (`troubleshooting_answers.md`) or inline in README:

### Q1 — Slow Dashboard (4 pts)
Dashboard widgets take 8+ seconds with 500K movements. Diagnose and fix. Provide suspected root cause, 3 diagnostic steps, and solution code.

### Q2 — Git Conflict Resolution (3 pts)
Two developers edited `ProductResource.php` simultaneously. Git shows a conflict between `numeric('unit_price')` and `money('unit_price')`. Explain your resolution process and how to prevent future conflicts.

### Q3 — 500 Error After Deployment (3 pts)
Post-deployment, all API endpoints return 500. Log shows `Unknown column 'products.moq'`. Immediate fix, root cause, and prevention measure?

---

## Bonus Questions (Optional, +10 pts)

Answer any that match your experience:

| Question | Points | Topic |
|----------|--------|-------|
| Docker CMD vs ENTRYPOINT | 3 | Container internals |
| Linux CPU diagnostics | 3 | Server administration |
| Flutter + Laravel architecture | 4 | Mobile integration |

---

## Submission Checklist

Before submitting your repository, verify:

- [ ] **README.md** exists with setup instructions, assumptions, time spent
- [ ] `.env.example` has all required variables (no real credentials)
- [ ] `composer install` + `npm install` works on fresh clone
- [ ] `php artisan migrate --seed` creates database (demo data)
- [ ] All sections A–F have deliverables
- [ ] Tests pass: `php artisan test` or `vendor/bin/pest`
- [ ] No hardcoded credentials or API keys in code
- [ ] Git history shows reasonable commits (not single giant commit)
- [ ] Create **Section F** in `README.md` to add any necessary explanation

### README Requirements

Your README must include:

```markdown
# Inventory Assessment

## Setup
1. Clone repo
2. cp .env.example .env
3. ...

## Assumptions
- [Any assumptions you made]

## Time Spent
| Section | Time |
|---------|------|
| A | 95 min |
| B | 55 min |
| ... | ... |

## Known Limitations
- [What you didn't complete or would improve]

## Bonus Completed
- [Any bonus questions answered]
```

---

## How to Submit

1. **Push to your repository**
   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/inventory-assessment.git
   git push -u origin main
   ```

2. **Share repository link** via email reply or submission form

3. **Ensure repository is:**
   - Public or accessible to interviewers
   - Includes all commits (don't squash to single commit)
   - No `vendor/` or `node_modules/` (use `.gitignore`)

---

## FAQ

**Can I use AI assistants (Copilot, ChatGPT)?**  
We expect you to understand and be able to explain every line of code you submit. AI-assisted coding is acceptable, but blind copy-paste without comprehension will be obvious during code review.

**What if I can't finish everything in 4 hours?**  
Submit what you have. Partial completion with quality > rushed everything. Prioritize Sections A → B → C → D.

**Can I use Laravel packages?**  
Core requirements (Filament, Sanctum, Livewire) are expected. Additional packages are fine if justified in README.

**Do I need to deploy this somewhere?**  
No. Local development server (`php artisan serve`) is sufficient. Section E requires configs only.

**What PHP / Laravel versions?**  
PHP 8.2+, Laravel 10+. Filament v4 (provided in starter). Livewire 3.x.

---

## What We Evaluate

| Aspect | Weight | Description |
|--------|--------|-------------|
| **Code Quality** | 25% | Clean, DRY, SOLID, readable |
| **Filament Proficiency** | 20% | Native v4 patterns, efficient queries |
| **Problem Solving** | 20% | Pragmatic trade-offs, edge cases |
| **Performance Awareness** | 15% | Indexes, N+1 avoidance, caching |
| **Testing** | 10% | Coverage of critical paths |
| **Documentation** | 10% | README clarity, inline comments |

### Red Flags (Automatic Concern)

- SQL injection vulnerabilities in user input
- Hardcoded passwords/API keys in code
- No validation on API endpoints
- CSRF disabled globally
- Giant single commit with no history

---

## Questions?

Reply to the assessment invitation email. We typically respond within 2 business hours during Jakarta business hours (WIB).

**Good luck! We're excited to see your approach.**

---

*Assessment version 1.0*
