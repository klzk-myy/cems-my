# CEMS-MY Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Goal:** Build a complete BNM-compliant Currency Exchange Management System with trading, compliance, accounting, and risk-based customer management modules.

**Architecture:** Laravel 11 + Slim 4 hybrid architecture. Laravel handles web UI, auth, and reporting. Slim handles transaction engine, rate API, accounting calculations, and risk scoring for audit transparency. Shared MySQL 8.0 with encrypted PII fields.

**Tech Stack:** PHP 8.2, Laravel 11, Slim 4, MySQL 8.0, Tailwind CSS 3.x, Alpine.js 3.x, Redis, BCMath

---

## Phase 1: Infrastructure (Week 1)

### Task 1.1: Create Project Structure

**Files:**
- Create: `docker-compose.yml`
- Create: `.env.example`
- Create: `.gitignore`

- [ ] **Step 1: Create Docker Compose Configuration**
```yaml
version: '3.8'

services:
  app:
    build:
      context: ./docker/php
      dockerfile: Dockerfile
    container_name: cems-app
    volumes:
      - ./:/var/www/html
      - ./docker/php/php.ini:/usr/local/etc/php/php.ini
    networks:
      - cems-network
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:alpine
    container_name: cems-nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./docker/nginx/ssl:/etc/nginx/ssl
    networks:
      - cems-network
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: cems-mysql
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init:/docker-entrypoint-initdb.d
    ports:
      - "3306:3306"
    networks:
      - cems-network

  redis:
    image: redis:alpine
    container_name: cems-redis
    ports:
      - "6379:6379"
    networks:
      - cems-network

volumes:
  mysql_data:

networks:
  cems-network:
    driver: bridge
```

- [ ] **Step 2: Create PHP Dockerfile**
```dockerfile
# docker/php/Dockerfile
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql zip mbstring bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
```

- [ ] **Step 3: Create Nginx Configuration**
```nginx
# docker/nginx/nginx.conf
events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    server {
        listen 80;
        server_name localhost;
        root /var/www/html/public;
        index index.php index.html;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
            fastcgi_pass app:9000;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
}
```

- [ ] **Step 4: Create Environment Template**
```bash
# .env.example
APP_NAME=CEMS-MY
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=cems_my
DB_USERNAME=cems_user
DB_PASSWORD=secure_password_here
DB_ROOT_PASSWORD=root_password_here

REDIS_HOST=redis
REDIS_PORT=6379

ENCRYPTION_KEY=your_32_char_encryption_key_here

# Rate API
EXCHANGE_RATE_API_KEY=your_api_key_here

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=480
```

- [ ] **Step 5: Create Gitignore**
```gitignore
# .gitignore
/vendor
/node_modules
/public/build
/public/hot
/public/storage
/storage/*.key
/storage/pail
.env
.env.backup
.phpunit.result.cache
Homestead.json
Homestead.yaml
npm-debug.log
yarn-error.log
/auth.json
/.fleet
/.idea
/.nova
/.vscode
/.zed
/docker/mysql/data
/docker/nginx/ssl/*.pem
/docker/nginx/ssl/*.key
```

- [ ] **Step 6: Commit**
```bash
git add docker-compose.yml .env.example .gitignore docker/
git commit -m "infra: setup docker environment with php, nginx, mysql, redis"
```

---

### Task 1.2: Create Database Schema

**Files:**
- Create: `database/migrations/2025_03_31_000001_create_users_table.php`
- Create: `database/migrations/2025_03_31_000002_create_customers_table.php`
- Create: `database/migrations/2025_03_31_000003_create_currencies_table.php`
- Create: `database/migrations/2025_03_31_000004_create_exchange_rates_table.php`
- Create: `database/migrations/2025_03_31_000005_create_transactions_table.php`
- Create: `database/migrations/2025_03_31_000006_create_system_logs_table.php`

- [ ] **Step 1: Create Users Migration**
```php
<?php
// database/migrations/2025_03_31_000001_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->enum('role', ['teller', 'manager', 'compliance_officer', 'admin'])
                  ->default('teller');
            $table->boolean('mfa_enabled')->default(false);
            $table->string('mfa_secret', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            
            $table->index('role');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

- [ ] **Step 2: Create Customers Migration**
```php
<?php
// database/migrations/2025_03_31_000002_create_customers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 255);
            $table->enum('id_type', ['MyKad', 'Passport', 'Others']);
            $table->binary('id_number_encrypted');
            $table->string('nationality', 100);
            $table->date('date_of_birth');
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('pep_status')->default(false);
            $table->integer('risk_score')->default(0);
            $table->enum('risk_rating', ['Low', 'Medium', 'High'])->default('Low');
            $table->timestamp('risk_assessed_at')->nullable();
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();
            
            $table->index('id_type');
            $table->index('nationality');
            $table->index('pep_status');
            $table->index('risk_rating');
            $table->index('last_transaction_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
```

- [ ] **Step 3: Create Currencies Migration**
```php
<?php
// database/migrations/2025_03_31_000003_create_currencies_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 3)->primary();
            $table->string('name', 100);
            $table->string('symbol', 10)->nullable();
            $table->tinyInteger('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('is_active');
        });
        
        // Insert base currencies
        DB::table('currencies')->insert([
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'decimal_places' => 2],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'decimal_places' => 2],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
```

- [ ] **Step 4: Create Exchange Rates Migration**
```php
<?php
// database/migrations/2025_03_31_000004_create_exchange_rates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code', 3);
            $table->decimal('rate_buy', 18, 6);
            $table->decimal('rate_sell', 18, 6);
            $table->string('source', 50);
            $table->timestamp('fetched_at');
            $table->timestamps();
            
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->index(['currency_code', 'fetched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
```

- [ ] **Step 5: Create Transactions Migration**
```php
<?php
// database/migrations/2025_03_31_000005_create_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['Buy', 'Sell']);
            $table->string('currency_code', 3);
            $table->decimal('amount_local', 18, 4);
            $table->decimal('amount_foreign', 18, 4);
            $table->decimal('rate', 18, 6);
            $table->text('purpose')->nullable();
            $table->string('source_of_funds', 255)->nullable();
            $table->enum('status', ['Pending', 'Completed', 'OnHold', 'Rejected', 'Reversed'])
                  ->default('Pending');
            $table->text('hold_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->enum('cdd_level', ['Simplified', 'Standard', 'Enhanced']);
            $table->timestamps();
            
            $table->index(['customer_id', 'created_at']);
            $table->index('status');
            $table->index(['type', 'currency_code']);
            $table->index('created_at');
            $table->index('amount_local');
            
            $table->foreign('currency_code')->references('code')->on('currencies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
```

- [ ] **Step 6: Create System Logs Migration**
```php
<?php
// database/migrations/2025_03_31_000006_create_system_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('action', 100);
            $table->string('entity_type', 50)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'action']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
```

- [ ] **Step 7: Run Migrations**
```bash
docker-compose up -d
docker-compose exec app php artisan migrate
```

- [ ] **Step 8: Commit**
```bash
git add database/migrations/
git commit -m "db: create core tables - users, customers, currencies, rates, transactions, logs"
```

---

### Task 1.3: Create Compliance Tables

**Files:**
- Create: `database/migrations/2025_03_31_000007_create_sanction_lists_table.php`
- Create: `database/migrations/2025_03_31_000008_create_sanction_entries_table.php`
- Create: `database/migrations/2025_03_31_000009_create_flagged_transactions_table.php`
- Create: `database/migrations/2025_03_31_000010_create_high_risk_countries_table.php`
- Create: `database/migrations/2025_03_31_000011_create_customer_risk_history_table.php`

- [ ] **Step 1: Create Sanction Lists Migration**
```php
<?php
// database/migrations/2025_03_31_000007_create_sanction_lists_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanction_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->enum('list_type', ['UNSCR', 'MOHA', 'Internal']);
            $table->string('source_file', 255)->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->boolean('is_active')->default(true);
            $table->timestamp('uploaded_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            
            $table->index('list_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanction_lists');
    }
};
```

- [ ] **Step 2: Create Sanction Entries Migration**
```php
<?php
// database/migrations/2025_03_31_000008_create_sanction_entries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanction_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('sanction_lists');
            $table->string('entity_name', 255);
            $table->enum('entity_type', ['Individual', 'Entity'])->default('Individual');
            $table->text('aliases')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->json('details')->nullable();
            
            $table->index('list_id');
            $table->index('entity_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanction_entries');
    }
};
```

- [ ] **Step 3: Create Flagged Transactions Migration**
```php
<?php
// database/migrations/2025_03_31_000009_create_flagged_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flagged_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained();
            $table->enum('flag_type', ['EDD_Required', 'Sanction_Match', 'Velocity', 'Structuring', 'Manual']);
            $table->text('flag_reason');
            $table->enum('status', ['Open', 'Under_Review', 'Resolved', 'Rejected'])->default('Open');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index('transaction_id');
            $table->index('status');
            $table->index('assigned_to');
            $table->index('flag_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flagged_transactions');
    }
};
```

- [ ] **Step 4: Create High Risk Countries Migration**
```php
<?php
// database/migrations/2025_03_31_000010_create_high_risk_countries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('high_risk_countries', function (Blueprint $table) {
            $table->string('country_code', 2)->primary();
            $table->string('country_name', 100);
            $table->enum('risk_level', ['High', 'Grey']);
            $table->string('source', 50);
            $table->date('list_date');
            $table->timestamps();
            
            $table->index('risk_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('high_risk_countries');
    }
};
```

- [ ] **Step 5: Create Risk History Migration**
```php
<?php
// database/migrations/2025_03_31_000011_create_customer_risk_history_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_risk_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->integer('old_score')->nullable();
            $table->integer('new_score');
            $table->enum('old_rating', ['Low', 'Medium', 'High'])->nullable();
            $table->enum('new_rating', ['Low', 'Medium', 'High']);
            $table->text('change_reason');
            $table->foreignId('assessed_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_risk_history');
    }
};
```

- [ ] **Step 6: Run Migrations**
```bash
docker-compose exec app php artisan migrate
```

- [ ] **Step 7: Commit**
```bash
git add database/migrations/
git commit -m "db: add compliance tables - sanctions, flags, risk history, high-risk countries"
```

---

### Task 1.4: Create Accounting Tables

**Files:**
- Create: `database/migrations/2025_03_31_000012_create_currency_positions_table.php`
- Create: `database/migrations/2025_03_31_000013_create_revaluation_entries_table.php`
- Create: `database/migrations/2025_03_31_000014_create_till_balances_table.php`
- Create: `database/migrations/2025_03_31_000015_create_chart_of_accounts_table.php`

- [ ] **Step 1: Create Currency Positions Migration**
```php
<?php
// database/migrations/2025_03_31_000012_create_currency_positions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_positions', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code', 3);
            $table->string('till_id', 50)->default('MAIN');
            $table->decimal('balance', 18, 4)->default(0);
            $table->decimal('avg_cost_rate', 18, 6)->nullable();
            $table->decimal('last_valuation_rate', 18, 6)->nullable();
            $table->decimal('unrealized_pnl', 18, 4)->default(0);
            $table->timestamp('last_valuation_at')->nullable();
            $table->timestamps();
            
            $table->unique(['currency_code', 'till_id']);
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->index('currency_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_positions');
    }
};
```

- [ ] **Step 2: Create Revaluation Entries Migration**
```php
<?php
// database/migrations/2025_03_31_000013_create_revaluation_entries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revaluation_entries', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code', 3);
            $table->string('till_id', 50)->default('MAIN');
            $table->decimal('old_rate', 18, 6);
            $table->decimal('new_rate', 18, 6);
            $table->decimal('position_amount', 18, 4);
            $table->decimal('gain_loss_amount', 18, 4);
            $table->date('revaluation_date');
            $table->foreignId('posted_by')->constrained('users');
            $table->timestamp('posted_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->index(['currency_code', 'revaluation_date']);
            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revaluation_entries');
    }
};
```

- [ ] **Step 3: Create Till Balances Migration**
```php
<?php
// database/migrations/2025_03_31_000014_create_till_balances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('till_balances', function (Blueprint $table) {
            $table->id();
            $table->string('till_id', 50);
            $table->string('currency_code', 3);
            $table->decimal('opening_balance', 18, 4);
            $table->decimal('closing_balance', 18, 4)->nullable();
            $table->decimal('variance', 18, 4)->nullable();
            $table->date('date');
            $table->foreignId('opened_by')->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->unique(['till_id', 'date', 'currency_code']);
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('till_balances');
    }
};
```

- [ ] **Step 4: Create Chart of Accounts Migration**
```php
<?php
// database/migrations/2025_03_31_000015_create_chart_of_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->string('account_code', 20)->primary();
            $table->string('account_name', 255);
            $table->enum('account_type', ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense']);
            $table->string('parent_code', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('parent_code')->references('account_code')->on('chart_of_accounts');
            $table->index('account_type');
        });
        
        // Insert base accounts
        DB::table('chart_of_accounts')->insert([
            ['account_code' => '1000', 'account_name' => 'Cash - MYR', 'account_type' => 'Asset'],
            ['account_code' => '1100', 'account_name' => 'Cash - USD', 'account_type' => 'Asset'],
            ['account_code' => '1200', 'account_name' => 'Cash - EUR', 'account_type' => 'Asset'],
            ['account_code' => '4000', 'account_name' => 'Revenue - Forex', 'account_type' => 'Revenue'],
            ['account_code' => '5000', 'account_name' => 'Expense - Revaluation Loss', 'account_type' => 'Expense'],
            ['account_code' => '5100', 'account_name' => 'Revenue - Revaluation Gain', 'account_type' => 'Revenue'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
```

- [ ] **Step 5: Run Migrations**
```bash
docker-compose exec app php artisan migrate
```

- [ ] **Step 6: Commit**
```bash
git add database/migrations/
git commit -m "db: add accounting tables - positions, revaluation, till balances, chart of accounts"
```

---

### Task 1.5: Create Security & PDPA Tables

**Files:**
- Create: `database/migrations/2025_03_31_000016_create_data_breach_alerts_table.php`
- Create: `database/migrations/2025_03_31_000017_create_customer_documents_table.php`

- [ ] **Step 1: Create Data Breach Alerts Migration**
```php
<?php
// database/migrations/2025_03_31_000016_create_data_breach_alerts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_breach_alerts', function (Blueprint $table) {
            $table->id();
            $table->enum('alert_type', ['Mass_Access', 'Unauthorized', 'Export_Anomaly']);
            $table->enum('severity', ['Low', 'Medium', 'High', 'Critical']);
            $table->text('description');
            $table->integer('record_count')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users');
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index('severity');
            $table->index('is_resolved');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_breach_alerts');
    }
};
```

- [ ] **Step 2: Create Customer Documents Migration**
```php
<?php
// database/migrations/2025_03_31_000017_create_customer_documents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->enum('document_type', ['MyKad', 'Passport', 'Proof_of_Address', 'Others']);
            $table->string('file_path', 500);
            $table->string('file_hash', 64);
            $table->integer('file_size')->nullable();
            $table->boolean('encrypted')->default(true);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            
            $table->index('customer_id');
            $table->index('document_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
    }
};
```

- [ ] **Step 3: Run All Migrations**
```bash
docker-compose exec app php artisan migrate
```

- [ ] **Step 4: Verify Schema**
```bash
docker-compose exec mysql mysql -u cems_user -p cems_my -e "SHOW TABLES;"
```

- [ ] **Step 5: Commit**
```bash
git add database/migrations/
git commit -m "db: add security tables - breach alerts, customer documents with encryption support"
```

---

## Phase 2: Core Engine (Week 2)

### Task 2.1: Initialize Laravel Project

**Files:**
- Create: `composer.json`
- Create: `artisan`
- Create: `public/index.php`
- Create: `config/` (all Laravel config files)

- [ ] **Step 1: Install Laravel**
```bash
docker-compose exec app composer create-project laravel/laravel . --no-interaction
```

- [ ] **Step 2: Configure Database Connection**
Edit `config/database.php`:
```php
'mysql' => [
    'driver' => 'mysql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', 'mysql'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'cems_my'),
    'username' => env('DB_USERNAME', 'cems_user'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => 'InnoDB',
],
```

- [ ] **Step 3: Configure Session (Redis)**
Edit `config/session.php`:
```php
'driver' => env('SESSION_DRIVER', 'redis'),
'lifetime' => env('SESSION_LIFETIME', 480),
'expire_on_close' => false,
```

- [ ] **Step 4: Configure Encryption Key**
Edit `config/app.php`:
```php
'key' => env('APP_KEY', 'base64:'.base64_encode(random_bytes(32))),
'cipher' => 'AES-256-CBC',
```

- [ ] **Step 5: Install Required Packages**
```bash
docker-compose exec app composer require predis/predis
```

- [ ] **Step 6: Generate Application Key**
```bash
docker-compose exec app php artisan key:generate
```

- [ ] **Step 7: Commit**
```bash
git add composer.json composer.lock config/ public/ artisan bootstrap/ storage/ routes/
git commit -m "core: initialize Laravel 11 with MySQL and Redis config"
```

---

### Task 2.2: Create Base Models

**Files:**
- Create: `app/Models/User.php`
- Create: `app/Models/Customer.php`
- Create: `app/Models/Transaction.php`
- Create: `app/Models/ExchangeRate.php`

- [ ] **Step 1: Create User Model**
```php
<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'username', 'email', 'password_hash', 'role', 
        'mfa_enabled', 'mfa_secret', 'is_active'
    ];

    protected $hidden = [
        'password_hash', 'mfa_secret',
    ];

    protected $casts = [
        'mfa_enabled' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isManager()
    {
        return in_array($this->role, ['manager', 'admin']);
    }

    public function isComplianceOfficer()
    {
        return $this->role === 'compliance_officer' || $this->isAdmin();
    }
}
```

- [ ] **Step 2: Create Customer Model**
```php
<?php
// app/Models/Customer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'full_name', 'id_type', 'id_number_encrypted', 'nationality',
        'date_of_birth', 'address', 'phone', 'email', 'pep_status',
        'risk_score', 'risk_rating', 'risk_assessed_at', 'last_transaction_at'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'pep_status' => 'boolean',
        'risk_score' => 'integer',
        'risk_assessed_at' => 'datetime',
        'last_transaction_at' => 'datetime',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function documents()
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function riskHistory()
    {
        return $this->hasMany(CustomerRiskHistory::class);
    }
}
```

- [ ] **Step 3: Create Transaction Model**
```php
<?php
// app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'customer_id', 'user_id', 'type', 'currency_code',
        'amount_local', 'amount_foreign', 'rate', 'purpose',
        'source_of_funds', 'status', 'hold_reason', 'approved_by',
        'approved_at', 'cdd_level'
    ];

    protected $casts = [
        'amount_local' => 'decimal:4',
        'amount_foreign' => 'decimal:4',
        'rate' => 'decimal:6',
        'approved_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code');
    }

    public function flags()
    {
        return $this->hasMany(FlaggedTransaction::class);
    }
}
```

- [ ] **Step 4: Create Exchange Rate Model**
```php
<?php
// app/Models/ExchangeRate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'currency_code', 'rate_buy', 'rate_sell', 'source', 'fetched_at'
    ];

    protected $casts = [
        'rate_buy' => 'decimal:6',
        'rate_sell' => 'decimal:6',
        'fetched_at' => 'datetime',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code');
    }

    public function scopeLatestRates($query)
    {
        return $query->orderBy('fetched_at', 'desc');
    }
}
```

- [ ] **Step 5: Create Currency Model**
```php
<?php
// app/Models/Currency.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code', 'name', 'symbol', 'decimal_places', 'is_active'
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_active' => 'boolean',
    ];

    public function exchangeRates()
    {
        return $this->hasMany(ExchangeRate::class, 'currency_code');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'currency_code');
    }
}
```

- [ ] **Step 6: Commit**
```bash
git add app/Models/
git commit -m "models: create base Eloquent models for users, customers, transactions, rates"
```

---

### Task 2.3: Implement Encryption Service

**Files:**
- Create: `app/Services/EncryptionService.php`
- Create: `tests/Unit/EncryptionServiceTest.php`

- [ ] **Step 1: Create Encryption Service**
```php
<?php
// app/Services/EncryptionService.php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class EncryptionService
{
    protected string $key;

    public function __construct()
    {
        $this->key = config('app.encryption_key') ?? env('ENCRYPTION_KEY');
        
        if (empty($this->key)) {
            throw new \RuntimeException('Encryption key not configured');
        }
    }

    public function encrypt(string $data): string
    {
        return openssl_encrypt(
            $data,
            'AES-256-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $this->getIv()
        );
    }

    public function decrypt(string $encryptedData): ?string
    {
        $result = openssl_decrypt(
            $encryptedData,
            'AES-256-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $this->getIv()
        );
        
        return $result !== false ? $result : null;
    }

    protected function getIv(): string
    {
        // In production, store IV alongside encrypted data
        return substr(hash('sha256', $this->key), 0, 16);
    }

    public function hash(string $data): string
    {
        return hash('sha256', $data . $this->key);
    }
}
```

- [ ] **Step 2: Create Encryption Test**
```php
<?php
// tests/Unit/EncryptionServiceTest.php

namespace Tests\Unit;

use App\Services\EncryptionService;
use Tests\TestCase;

class EncryptionServiceTest extends TestCase
{
    protected EncryptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EncryptionService();
    }

    public function test_can_encrypt_and_decrypt_data()
    {
        $original = 'MyKad: 900101-01-1234';
        
        $encrypted = $this->service->encrypt($original);
        $decrypted = $this->service->decrypt($encrypted);
        
        $this->assertNotEquals($original, $encrypted);
        $this->assertEquals($original, $decrypted);
    }

    public function test_encrypts_to_different_values()
    {
        $data = 'sensitive data';
        
        $encrypted1 = $this->service->encrypt($data);
        $encrypted2 = $this->service->encrypt($data);
        
        $this->assertEquals($this->service->decrypt($encrypted1), $this->service->decrypt($encrypted2));
    }

    public function test_hashing_is_deterministic()
    {
        $data = 'test data';
        
        $hash1 = $this->service->hash($data);
        $hash2 = $this->service->hash($data);
        
        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1)); // SHA256 length
    }
}
```

- [ ] **Step 3: Run Tests**
```bash
docker-compose exec app php artisan test tests/Unit/EncryptionServiceTest.php
```

Expected: 3 tests, all passing

- [ ] **Step 4: Commit**
```bash
git add app/Services/EncryptionService.php tests/Unit/EncryptionServiceTest.php
git commit -m "feat: implement AES-256 encryption service for PII protection"
```

---

### Task 2.4: Implement BCMath Service

**Files:**
- Create: `app/Services/MathService.php`
- Create: `tests/Unit/MathServiceTest.php`

- [ ] **Step 1: Create Math Service**
```php
<?php
// app/Services/MathService.php

namespace App\Services;

class MathService
{
    protected int $scale = 6;

    public function __construct(int $scale = 6)
    {
        $this->scale = $scale;
    }

    public function add(string $a, string $b): string
    {
        return bcadd($a, $b, $this->scale);
    }

    public function subtract(string $a, string $b): string
    {
        return bcsub($a, $b, $this->scale);
    }

    public function multiply(string $a, string $b): string
    {
        return bcmul($a, $b, $this->scale);
    }

    public function divide(string $a, string $b): string
    {
        if (bccomp($b, '0', $this->scale) === 0) {
            throw new \InvalidArgumentException('Division by zero');
        }
        return bcdiv($a, $b, $this->scale);
    }

    public function compare(string $a, string $b): int
    {
        return bccomp($a, $b, $this->scale);
    }

    public function calculateAverageCost(
        string $oldBalance,
        string $oldAvgCost,
        string $transactionAmount,
        string $transactionRate
    ): string {
        $oldValue = $this->multiply($oldBalance, $oldAvgCost);
        $newValue = $this->multiply($transactionAmount, $transactionRate);
        $totalValue = $this->add($oldValue, $newValue);
        $newBalance = $this->add($oldBalance, $transactionAmount);
        
        return $this->divide($totalValue, $newBalance);
    }

    public function calculateRevaluationPnl(
        string $positionAmount,
        string $oldRate,
        string $newRate
    ): string {
        $rateDiff = $this->subtract($newRate, $oldRate);
        return $this->multiply($positionAmount, $rateDiff);
    }

    public function calculateTransactionAmount(
        string $foreignAmount,
        string $rate,
        string $type = 'Buy'
    ): string {
        $amount = $this->multiply($foreignAmount, $rate);
        
        if ($type === 'Sell') {
            return $amount;
        }
        
        return $amount;
    }
}
```

- [ ] **Step 2: Create Math Service Tests**
```php
<?php
// tests/Unit/MathServiceTest.php

namespace Tests\Unit;

use App\Services\MathService;
use Tests\TestCase;

class MathServiceTest extends TestCase
{
    protected MathService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MathService();
    }

    public function test_basic_arithmetic_operations()
    {
        $this->assertEquals('5.000000', $this->service->add('2', '3'));
        $this->assertEquals('3.000000', $this->service->subtract('5', '2'));
        $this->assertEquals('6.000000', $this->service->multiply('2', '3'));
        $this->assertEquals('2.500000', $this->service->divide('5', '2'));
    }

    public function test_calculate_average_cost()
    {
        // Old: 1000 USD @ 4.50 = 4500 MYR cost
        // New: 500 USD @ 4.70 = 2350 MYR cost
        // Total: 1500 USD @ avg 4.566667
        $result = $this->service->calculateAverageCost(
            '1000',      // old balance
            '4.50',      // old avg cost
            '500',       // transaction amount
            '4.70'       // transaction rate
        );
        
        $this->assertEquals('4.566667', $result);
    }

    public function test_calculate_revaluation_pnl()
    {
        // Position: 1000 USD
        // Old rate: 4.50, New rate: 4.70
        // Gain: 1000 * (4.70 - 4.50) = 200 MYR
        $result = $this->service->calculateRevaluationPnl('1000', '4.50', '4.70');
        
        $this->assertEquals('200.000000', $result);
    }

    public function test_calculate_transaction_amount()
    {
        // Buy 100 USD @ 4.70 = 470 MYR
        $result = $this->service->calculateTransactionAmount('100', '4.70', 'Buy');
        
        $this->assertEquals('470.000000', $result);
    }

    public function test_compare_values()
    {
        $this->assertEquals(1, $this->service->compare('5', '3'));
        $this->assertEquals(-1, $this->service->compare('3', '5'));
        $this->assertEquals(0, $this->service->compare('5', '5'));
    }

    public function test_division_by_zero_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Division by zero');
        
        $this->service->divide('10', '0');
    }
}
```

- [ ] **Step 3: Run Tests**
```bash
docker-compose exec app php artisan test tests/Unit/MathServiceTest.php
```

Expected: 6 tests, all passing

- [ ] **Step 4: Commit**
```bash
git add app/Services/MathService.php tests/Unit/MathServiceTest.php
git commit -m "feat: implement BCMath service for precise financial calculations"
```

---

### Task 2.5: Implement Rate API Service

**Files:**
- Create: `app/Services/RateApiService.php`
- Create: `tests/Unit/RateApiServiceTest.php`

- [ ] **Step 1: Create Rate API Service**
```php
<?php
// app/Services/RateApiService.php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RateApiService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $cacheDuration = 60; // seconds

    public function __construct()
    {
        $this->apiKey = config('services.exchange_rate_api.key');
        $this->baseUrl = 'https://api.exchangerate-api.com/v4';
    }

    public function fetchLatestRates(): array
    {
        return Cache::remember('exchange_rates', $this->cacheDuration, function () {
            $response = Http::get("{$this->baseUrl}/latest/MYR");
            
            if (!$response->successful()) {
                throw new \RuntimeException('Failed to fetch exchange rates: ' . $response->body());
            }
            
            $data = $response->json();
            
            if (!isset($data['rates'])) {
                throw new \RuntimeException('Invalid API response format');
            }
            
            return $this->processRates($data['rates'], $data['time_last_updated'] ?? time());
        });
    }

    protected function processRates(array $rates, $timestamp): array
    {
        $processed = [];
        $currencies = ['USD', 'EUR', 'GBP', 'SGD', 'AUD', 'CAD', 'CHF', 'JPY'];
        
        foreach ($currencies as $currency) {
            if (isset($rates[$currency])) {
                $rate = $rates[$currency];
                // Add spread for buy/sell
                $spread = 0.02; // 2% spread
                $processed[$currency] = [
                    'buy' => $this->roundRate($rate * (1 - $spread / 2)),
                    'sell' => $this->roundRate($rate * (1 + $spread / 2)),
                    'mid' => $this->roundRate($rate),
                    'timestamp' => $timestamp,
                ];
            }
        }
        
        return $processed;
    }

    protected function roundRate(float $rate): float
    {
        return round($rate, 6);
    }

    public function getRateForCurrency(string $currency): ?array
    {
        $rates = $this->fetchLatestRates();
        return $rates[$currency] ?? null;
    }

    public function clearCache(): void
    {
        Cache::forget('exchange_rates');
    }
}
```

- [ ] **Step 2: Create Rate API Service Tests**
```php
<?php
// tests/Unit/RateApiServiceTest.php

namespace Tests\Unit;

use App\Services\RateApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RateApiServiceTest extends TestCase
{
    protected RateApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RateApiService();
        Cache::flush();
    }

    public function test_fetches_and_caches_rates()
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 0.212,
                    'EUR' => 0.195,
                    'GBP' => 0.168,
                ],
                'time_last_updated' => time(),
            ], 200),
        ]);
        
        $rates = $this->service->fetchLatestRates();
        
        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('buy', $rates['USD']);
        $this->assertArrayHasKey('sell', $rates['USD']);
        $this->assertGreaterThan($rates['USD']['buy'], $rates['USD']['sell']);
        
        // Verify caching
        $this->assertTrue(Cache::has('exchange_rates'));
    }

    public function test_gets_rate_for_specific_currency()
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => ['USD' => 0.212],
                'time_last_updated' => time(),
            ], 200),
        ]);
        
        $rate = $this->service->getRateForCurrency('USD');
        
        $this->assertIsArray($rate);
        $this->assertArrayHasKey('buy', $rate);
        $this->assertArrayHasKey('sell', $rate);
    }

    public function test_returns_null_for_unknown_currency()
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => ['USD' => 0.212],
                'time_last_updated' => time(),
            ], 200),
        ]);
        
        $rate = $this->service->getRateForCurrency('XYZ');
        
        $this->assertNull($rate);
    }

    public function test_throws_exception_on_api_failure()
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response('Error', 500),
        ]);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch exchange rates');
        
        $this->service->fetchLatestRates();
    }
}
```

- [ ] **Step 3: Add Rate API Config**
Edit `config/services.php`:
```php
'exchange_rate_api' => [
    'key' => env('EXCHANGE_RATE_API_KEY'),
],
```

- [ ] **Step 4: Run Tests**
```bash
docker-compose exec app php artisan test tests/Unit/RateApiServiceTest.php
```

Expected: 4 tests, all passing

- [ ] **Step 5: Commit**
```bash
git add app/Services/RateApiService.php config/services.php tests/Unit/RateApiServiceTest.php
git commit -m "feat: implement exchange rate API service with caching and spread calculation"
```

---

## Phase 3: Compliance Module (Week 3)

### Task 3.1: Implement CDD/EDD Logic

**Files:**
- Create: `app/Services/ComplianceService.php`
- Create: `tests/Unit/ComplianceServiceTest.php`

- [ ] **Step 1: Create Compliance Service**
```php
<?php
// app/Services/ComplianceService.php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ComplianceService
{
    protected EncryptionService $encryptionService;
    protected MathService $mathService;

    public function __construct(
        EncryptionService $encryptionService,
        MathService $mathService
    ) {
        $this->encryptionService = $encryptionService;
        $this->mathService = $mathService;
    }

    public function determineCDDLevel(float $amount, Customer $customer): string
    {
        // Enhanced Due Diligence triggers
        if ($customer->pep_status || $this->checkSanctionMatch($customer)) {
            return 'Enhanced';
        }
        
        if ($amount >= 50000 || $customer->risk_rating === 'High') {
            return 'Enhanced';
        }
        
        if ($amount >= 3000) {
            return 'Standard';
        }
        
        return 'Simplified';
    }

    public function checkSanctionMatch(Customer $customer): bool
    {
        // Query sanction_entries for fuzzy match
        $matches = DB::table('sanction_entries')
            ->whereRaw('LOWER(entity_name) LIKE ?', ['%' . strtolower($customer->full_name) . '%'])
            ->orWhereRaw('LOWER(aliases) LIKE ?', ['%' . strtolower($customer->full_name) . '%'])
            ->count();
        
        return $matches > 0;
    }

    public function checkVelocity(int $customerId, float $newAmount): array
    {
        $startTime = now()->subHours(24);
        
        $velocity = Transaction::where('customer_id', $customerId)
            ->where('created_at', '>=', $startTime)
            ->sum('amount_local');
        
        $total = $velocity + $newAmount;
        
        return [
            'amount_24h' => (float) $velocity,
            'with_new_transaction' => $total,
            'threshold_exceeded' => $total > 50000,
            'threshold_amount' => 50000,
        ];
    }

    public function checkStructuring(int $customerId): bool
    {
        $oneHourAgo = now()->subHour();
        
        $smallTransactions = Transaction::where('customer_id', $customerId)
            ->where('created_at', '>=', $oneHourAgo)
            ->where('amount_local', '<', 3000)
            ->count();
        
        return $smallTransactions >= 3;
    }

    public function requiresHold(float $amount, Customer $customer): array
    {
        $reasons = [];
        
        if ($amount >= 50000) {
            $reasons[] = 'EDD_Required';
        }
        
        if ($customer->pep_status) {
            $reasons[] = 'PEP_Status';
        }
        
        if ($this->checkSanctionMatch($customer)) {
            $reasons[] = 'Sanction_Match';
        }
        
        if ($customer->risk_rating === 'High') {
            $reasons[] = 'High_Risk_Customer';
        }
        
        return [
            'requires_hold' => !empty($reasons),
            'reasons' => $reasons,
        ];
    }
}
```

- [ ] **Step 2: Create Compliance Service Tests**
```php
<?php
// tests/Unit/ComplianceServiceTest.php

namespace Tests\Unit;

use App\Models\Customer;
use App\Services\ComplianceService;
use App\Services\EncryptionService;
use App\Services\MathService;
use Tests\TestCase;

class ComplianceServiceTest extends TestCase
{
    protected ComplianceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComplianceService(
            new EncryptionService(),
            new MathService()
        );
    }

    public function test_simplified_cdd_for_small_amounts()
    {
        $customer = new Customer(['pep_status' => false, 'risk_rating' => 'Low']);
        
        $level = $this->service->determineCDDLevel(1000, $customer);
        
        $this->assertEquals('Simplified', $level);
    }

    public function test_standard_cdd_for_medium_amounts()
    {
        $customer = new Customer(['pep_status' => false, 'risk_rating' => 'Low']);
        
        $level = $this->service->determineCDDLevel(5000, $customer);
        
        $this->assertEquals('Standard', $level);
    }

    public function test_enhanced_cdd_for_large_amounts()
    {
        $customer = new Customer(['pep_status' => false, 'risk_rating' => 'Low']);
        
        $level = $this->service->determineCDDLevel(60000, $customer);
        
        $this->assertEquals('Enhanced', $level);
    }

    public function test_enhanced_cdd_for_pep()
    {
        $customer = new Customer(['pep_status' => true, 'risk_rating' => 'Low']);
        
        $level = $this->service->determineCDDLevel(1000, $customer);
        
        $this->assertEquals('Enhanced', $level);
    }

    public function test_requires_hold_for_large_amounts()
    {
        $customer = new Customer(['pep_status' => false, 'risk_rating' => 'Low']);
        
        $result = $this->service->requiresHold(60000, $customer);
        
        $this->assertTrue($result['requires_hold']);
        $this->assertContains('EDD_Required', $result['reasons']);
    }
}
```

- [ ] **Step 3: Run Tests**
```bash
docker-compose exec app php artisan test tests/Unit/ComplianceServiceTest.php
```

Expected: 5 tests, all passing

- [ ] **Step 4: Commit**
```bash
git add app/Services/ComplianceService.php tests/Unit/ComplianceServiceTest.php
git commit -m "feat: implement CDD/EDD compliance logic with thresholds and holds"
```

---

### Task 3.2: Implement Customer Risk Rating

**Files:**
- Create: `app/Services/RiskRatingService.php`
- Create: `tests/Unit/RiskRatingServiceTest.php`

- [ ] **Step 1: Create Risk Rating Service**
```php
<?php
// app/Services/RiskRatingService.php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerRiskHistory;
use Illuminate\Support\Facades\DB;

class RiskRatingService
{
    protected array $riskFactors = [
        'pep_status' => 40,
        'high_risk_country' => 30,
        'complex_ownership' => 25,
        'cash_intensive' => 20,
        'unusual_pattern' => 10,
    ];

    public function calculateRiskScore(Customer $customer): int
    {
        $score = 0;
        
        // PEP status check
        if ($customer->pep_status) {
            $score += $this->riskFactors['pep_status'];
        }
        
        // High-risk country check
        if ($this->isHighRiskCountry($customer->nationality)) {
            $score += $this->riskFactors['high_risk_country'];
        }
        
        // Cash-intensive pattern check
        if ($this->isCashIntensive($customer->id)) {
            $score += $this->riskFactors['cash_intensive'];
        }
        
        return min($score, 100);
    }

    public function getRiskRating(int $score): string
    {
        if ($score <= 30) {
            return 'Low';
        }
        if ($score <= 60) {
            return 'Medium';
        }
        return 'High';
    }

    public function assessCustomer(Customer $customer, ?int $assessedBy = null): array
    {
        $oldScore = $customer->risk_score;
        $oldRating = $customer->risk_rating;
        
        $newScore = $this->calculateRiskScore($customer);
        $newRating = $this->getRiskRating($newScore);
        
        $customer->update([
            'risk_score' => $newScore,
            'risk_rating' => $newRating,
            'risk_assessed_at' => now(),
        ]);
        
        // Log the change
        CustomerRiskHistory::create([
            'customer_id' => $customer->id,
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'old_rating' => $oldRating,
            'new_rating' => $newRating,
            'change_reason' => 'Automated risk assessment',
            'assessed_by' => $assessedBy,
        ]);
        
        return [
            'score' => $newScore,
            'rating' => $newRating,
            'changed' => $oldScore !== $newScore,
        ];
    }

    protected function isHighRiskCountry(string $nationality): bool
    {
        return DB::table('high_risk_countries')
            ->where('country_name', $nationality)
            ->exists();
    }

    protected function isCashIntensive(int $customerId): bool
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        $largeCashCount = DB::table('transactions')
            ->where('customer_id', $customerId)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->where('amount_local', '>', 10000)
            ->count();
        
        return $largeCashCount > 3;
    }

    public function getRefreshFrequency(string $rating): int
    {
        return match ($rating) {
            'Low' => 3,    // 3 years
            'Medium' => 2, // 2 years
            'High' => 1,   // 1 year
        };
    }
}
```

- [ ] **Step 2: Create Risk Rating Tests**
```php
<?php
// tests/Unit/RiskRatingServiceTest.php

namespace Tests\Unit;

use App\Models\Customer;
use App\Services\RiskRatingService;
use Tests\TestCase;

class RiskRatingServiceTest extends TestCase
{
    protected RiskRatingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RiskRatingService();
    }

    public function test_calculate_score_for_low_risk_customer()
    {
        $customer = new Customer([
            'pep_status' => false,
            'nationality' => 'Malaysia',
        ]);
        
        $score = $this->service->calculateRiskScore($customer);
        
        $this->assertLessThanOrEqual(30, $score);
    }

    public function test_calculate_score_for_pep_customer()
    {
        $customer = new Customer([
            'pep_status' => true,
            'nationality' => 'Malaysia',
        ]);
        
        $score = $this->service->calculateRiskScore($customer);
        
        $this->assertGreaterThanOrEqual(40, $score);
    }

    public function test_get_low_rating_for_low_score()
    {
        $rating = $this->service->getRiskRating(25);
        
        $this->assertEquals('Low', $rating);
    }

    public function test_get_medium_rating_for_medium_score()
    {
        $rating = $this->service->getRiskRating(45);
        
        $this->assertEquals('Medium', $rating);
    }

    public function test_get_high_rating_for_high_score()
    {
        $rating = $this->service->getRiskRating(75);
        
        $this->assertEquals('High', $rating);
    }

    public function test_get_refresh_frequency_by_rating()
    {
        $this->assertEquals(3, $this->service->getRefreshFrequency('Low'));
        $this->assertEquals(2, $this->service->getRefreshFrequency('Medium'));
        $this->assertEquals(1, $this->service->getRefreshFrequency('High'));
    }
}
```

- [ ] **Step 3: Run Tests**
```bash
docker-compose exec app php artisan test tests/Unit/RiskRatingServiceTest.php
```

Expected: 6 tests, all passing

- [ ] **Step 4: Commit**
```bash
git add app/Services/RiskRatingService.php tests/Unit/RiskRatingServiceTest.php
git commit -m "feat: implement automated customer risk rating with scoring algorithm"
```

---

### Task 3.3: Implement Sanction List Screening

**Files:**
- Create: `app/Services/SanctionScreeningService.php`
- Create: `app/Http/Controllers/SanctionController.php`

- [ ] **Step 1: Create Sanction Screening Service**
```php
<?php
// app/Services/SanctionScreeningService.php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SanctionScreeningService
{
    protected float $matchThreshold = 0.80;

    public function screenName(string $name): array
    {
        $matches = [];
        $name = strtolower(trim($name));
        $nameParts = explode(' ', $name);
        
        // Query sanction entries
        $entries = DB::table('sanction_entries')
            ->select('id', 'entity_name', 'aliases', 'entity_type')
            ->get();
        
        foreach ($entries as $entry) {
            $entryName = strtolower($entry->entity_name);
            $aliases = $entry->aliases ? strtolower($entry->aliases) : '';
            
            $score = $this->calculateSimilarity($name, $entryName);
            $aliasScore = $this->checkAliases($name, $aliases);
            
            $maxScore = max($score, $aliasScore);
            
            if ($maxScore >= $this->matchThreshold) {
                $matches[] = [
                    'entry_id' => $entry->id,
                    'entity_name' => $entry->entity_name,
                    'entity_type' => $entry->entity_type,
                    'match_score' => round($maxScore, 2),
                    'match_type' => $score > $aliasScore ? 'Name' : 'Alias',
                ];
            }
        }
        
        // Sort by match score descending
        usort($matches, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
        
        return $matches;
    }

    protected function calculateSimilarity(string $str1, string $str2): float
    {
        $distance = levenshtein($str1, $str2);
        $maxLen = max(strlen($str1), strlen($str2));
        
        if ($maxLen === 0) {
            return 1.0;
        }
        
        return 1 - ($distance / $maxLen);
    }

    protected function checkAliases(string $name, string $aliases): float
    {
        if (empty($aliases)) {
            return 0.0;
        }
        
        $aliasList = array_map('trim', explode(',', $aliases));
        $maxScore = 0.0;
        
        foreach ($aliasList as $alias) {
            $score = $this->calculateSimilarity($name, $alias);
            $maxScore = max($maxScore, $score);
        }
        
        return $maxScore;
    }

    public function importSanctionList(string $filePath, int $uploadedBy): int
    {
        $listId = DB::table('sanction_lists')->insertGetId([
            'name' => basename($filePath),
            'list_type' => $this->detectListType($filePath),
            'source_file' => $filePath,
            'uploaded_by' => $uploadedBy,
            'uploaded_at' => now(),
        ]);
        
        $count = $this->processCsvFile($filePath, $listId);
        
        return $count;
    }

    protected function detectListType(string $filePath): string
    {
        $name = strtolower($filePath);
        if (str_contains($name, 'unscr')) return 'UNSCR';
        if (str_contains($name, 'moha')) return 'MOHA';
        return 'Internal';
    }

    protected function processCsvFile(string $filePath, int $listId): int
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Cannot open file: ' . $filePath);
        }
        
        $headers = fgetcsv($handle);
        $count = 0;
        
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            
            DB::table('sanction_entries')->insert([
                'list_id' => $listId,
                'entity_name' => $data['name'] ?? '',
                'entity_type' => $data['entity_type'] ?? 'Individual',
                'aliases' => $data['aliases'] ?? null,
                'nationality' => $data['nationality'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'details' => json_encode($data),
            ]);
            
            $count++;
        }
        
        fclose($handle);
        return $count;
    }
}
```

- [ ] **Step 2: Create Sanction Controller**
```php
<?php
// app/Http/Controllers/SanctionController.php

namespace App\Http\Controllers;

use App\Services\SanctionScreeningService;
use Illuminate\Http\Request;

class SanctionController extends Controller
{
    protected SanctionScreeningService $screeningService;

    public function __construct(SanctionScreeningService $screeningService)
    {
        $this->screeningService = $screeningService;
    }

    public function search(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3',
        ]);
        
        $matches = $this->screeningService->screenName($request->name);
        
        return response()->json([
            'query' => $request->name,
            'matches' => $matches,
            'count' => count($matches),
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);
        
        $file = $request->file('file');
        $path = $file->store('sanction_lists');
        
        $count = $this->screeningService->importSanctionList(
            storage_path('app/' . $path),
            auth()->id()
        );
        
        return response()->json([
            'message' => 'Sanction list imported successfully',
            'entries_imported' => $count,
        ]);
    }
}
```

- [ ] **Step 3: Add Routes**
Edit `routes/api.php`:
```php
use App\Http\Controllers\SanctionController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sanctions/search', [SanctionController::class, 'search']);
    Route::post('/sanctions/upload', [SanctionController::class, 'upload']);
});
```

- [ ] **Step 4: Commit**
```bash
git add app/Services/SanctionScreeningService.php app/Http/Controllers/SanctionController.php routes/api.php
git commit -m "feat: implement sanction list screening with fuzzy matching and CSV import"
```

---

## Phase 4: Accounting & Risk Monitoring (Week 4)

### Task 4.1: Implement Currency Position Tracking

**Files:**
- Create: `app/Services/CurrencyPositionService.php`
- Create: `tests/Unit/CurrencyPositionServiceTest.php`

- [ ] **Step 1: Create Currency Position Service**
```php
<?php
// app/Services/CurrencyPositionService.php

namespace App\Services;

use App\Models\CurrencyPosition;
use Illuminate\Support\Facades\DB;

class CurrencyPositionService
{
    protected MathService $mathService;

    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    public function updatePosition(
        string $currencyCode,
        string $amount,
        string $rate,
        string $type,
        string $tillId = 'MAIN'
    ): CurrencyPosition {
        return DB::transaction(function () use ($currencyCode, $amount, $rate, $type, $tillId) {
            $position = CurrencyPosition::firstOrCreate(
                ['currency_code' => $currencyCode, 'till_id' => $tillId],
                [
                    'balance' => '0',
                    'avg_cost_rate' => $rate,
                    'last_valuation_rate' => $rate,
                ]
            );
            
            $oldBalance = $position->balance;
            $oldAvgCost = $position->avg_cost_rate;
            
            if ($type === 'Buy') {
                // Buying foreign currency - increase position
                $newBalance = $this->mathService->add($oldBalance, $amount);
                
                if ($this->mathService->compare($oldBalance, '0') > 0) {
                    $newAvgCost = $this->mathService->calculateAverageCost(
                        $oldBalance,
                        $oldAvgCost,
                        $amount,
                        $rate
                    );
                } else {
                    $newAvgCost = $rate;
                }
            } else {
                // Selling foreign currency - decrease position
                $newBalance = $this->mathService->subtract($oldBalance, $amount);
                $newAvgCost = $oldAvgCost; // Cost basis doesn't change on sale
            }
            
            $position->update([
                'balance' => $newBalance,
                'avg_cost_rate' => $newAvgCost,
            ]);
            
            return $position->fresh();
        });
    }

    public function getPosition(string $currencyCode, string $tillId = 'MAIN'): ?CurrencyPosition
    {
        return CurrencyPosition::where('currency_code', $currencyCode)
            ->where('till_id', $tillId)
            ->first();
    }

    public function getAllPositions(string $tillId = 'MAIN'): array
    {
        return CurrencyPosition::where('till_id', $tillId)
            ->with('currency')
            ->get()
            ->toArray();
    }

    public function getTotalPnl(string $tillId = 'MAIN'): array
    {
        $positions = $this->getAllPositions($tillId);
        $totalUnrealized = '0';
        
        foreach ($positions as $position) {
            $totalUnrealized = $this->mathService->add(
                $totalUnrealized,
                $position['unrealized_pnl']
            );
        }
        
        return [
            'unrealized_pnl' => $totalUnrealized,
            'position_count' => count($positions),
        ];
    }
}
```

- [ ] **Step 2: Create Currency Position Model**
```php
<?php
// app/Models/CurrencyPosition.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyPosition extends Model
{
    protected $fillable = [
        'currency_code', 'till_id', 'balance', 'avg_cost_rate',
        'last_valuation_rate', 'unrealized_pnl', 'last_valuation_at'
    ];

    protected $casts = [
        'balance' => 'decimal:4',
        'avg_cost_rate' => 'decimal:6',
        'last_valuation_rate' => 'decimal:6',
        'unrealized_pnl' => 'decimal:4',
        'last_valuation_at' => 'datetime',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code');
    }
}
```

- [ ] **Step 3: Create Currency Position Tests**
```php
<?php
// tests/Unit/CurrencyPositionServiceTest.php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Services\CurrencyPositionService;
use App\Services\MathService;
use Tests\TestCase;

class CurrencyPositionServiceTest extends TestCase
{
    protected CurrencyPositionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CurrencyPositionService(new MathService());
        
        // Create test currency
        Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
        ]);
    }

    public function test_creates_position_on_first_buy()
    {
        $position = $this->service->updatePosition('USD', '1000', '4.50', 'Buy');
        
        $this->assertEquals('1000.0000', $position->balance);
        $this->assertEquals('4.500000', $position->avg_cost_rate);
    }

    public function test_updates_position_on_additional_buy()
    {
        // First buy: 1000 USD @ 4.50
        $this->service->updatePosition('USD', '1000', '4.50', 'Buy');
        
        // Second buy: 500 USD @ 4.70
        $position = $this->service->updatePosition('USD', '500', '4.70', 'Buy');
        
        // Expected: 1500 USD @ avg 4.566667
        $this->assertEquals('1500.0000', $position->balance);
        $this->assertEquals('4.566667', $position->avg_cost_rate);
    }

    public function test_decreases_position_on_sell()
    {
        // Setup: 1000 USD
        $this->service->updatePosition('USD', '1000', '4.50', 'Buy');
        
        // Sell 300 USD
        $position = $this->service->updatePosition('USD', '300', '4.70', 'Sell');
        
        // Expected: 700 USD, avg cost unchanged
        $this->assertEquals('700.0000', $position->balance);
        $this->assertEquals('4.500000', $position->avg_cost_rate);
    }

    public function test_gets_position_by_currency()
    {
        $this->service->updatePosition('USD', '1000', '4.50', 'Buy');
        
        $position = $this->service->getPosition('USD');
        
        $this->assertNotNull($position);
        $this->assertEquals('USD', $position->currency_code);
    }
}
```

- [ ] **Step 4: Run Tests**
```bash
docker-compose exec app php artisan test tests/Unit/CurrencyPositionServiceTest.php
```

Expected: 4 tests, all passing

- [ ] **Step 5: Commit**
```bash
git add app/Services/CurrencyPositionService.php app/Models/CurrencyPosition.php tests/Unit/CurrencyPositionServiceTest.php
git commit -m "feat: implement real-time currency position tracking with average cost calculation"
```

---

### Task 4.2: Implement Automatic Monthly Revaluation

**Files:**
- Create: `app/Services/RevaluationService.php`
- Create: `app/Console/Commands/RunMonthlyRevaluation.php`
- Create: `tests/Unit/RevaluationServiceTest.php`

- [ ] **Step 1: Create Revaluation Service**
```php
<?php
// app/Services/RevaluationService.php

namespace App\Services;

use App\Models\CurrencyPosition;
use App\Models\RevaluationEntry;
use Illuminate\Support\Facades\DB;

class RevaluationService
{
    protected MathService $mathService;
    protected RateApiService $rateApiService;

    public function __construct(
        MathService $mathService,
        RateApiService $rateApiService
    ) {
        $this->mathService = $mathService;
        $this->rateApiService = $rateApiService;
    }

    public function runRevaluation(int $postedBy, ?string $tillId = null): array
    {
        $tillId = $tillId ?? 'MAIN';
        $revaluationDate = now()->toDateString();
        $results = [];
        
        $positions = CurrencyPosition::where('till_id', $tillId)
            ->where('balance', '!=', 0)
            ->get();
        
        foreach ($positions as $position) {
            $result = $this->revaluePosition($position, $revaluationDate, $postedBy);
            if ($result) {
                $results[] = $result;
            }
        }
        
        return [
            'date' => $revaluationDate,
            'till_id' => $tillId,
            'positions_revalued' => count($results),
            'entries' => $results,
        ];
    }

    protected function revaluePosition(CurrencyPosition $position, string $date, int $postedBy): ?array
    {
        $newRate = $this->getCurrentRate($position->currency_code);
        
        if (!$newRate) {
            return null;
        }
        
        $oldRate = $position->last_valuation_rate ?? $position->avg_cost_rate;
        
        $gainLoss = $this->mathService->calculateRevaluationPnl(
            $position->balance,
            $oldRate,
            $newRate
        );
        
        return DB::transaction(function () use ($position, $oldRate, $newRate, $gainLoss, $date, $postedBy) {
            // Create revaluation entry
            $entry = RevaluationEntry::create([
                'currency_code' => $position->currency_code,
                'till_id' => $position->till_id,
                'old_rate' => $oldRate,
                'new_rate' => $newRate,
                'position_amount' => $position->balance,
                'gain_loss_amount' => $gainLoss,
                'revaluation_date' => $date,
                'posted_by' => $postedBy,
            ]);
            
            // Update position
            $cumulativePnl = $this->mathService->add(
                $position->unrealized_pnl,
                $gainLoss
            );
            
            $position->update([
                'last_valuation_rate' => $newRate,
                'unrealized_pnl' => $cumulativePnl,
                'last_valuation_at' => now(),
            ]);
            
            return [
                'entry_id' => $entry->id,
                'currency' => $position->currency_code,
                'old_rate' => $oldRate,
                'new_rate' => $newRate,
                'gain_loss' => $gainLoss,
            ];
        });
    }

    protected function getCurrentRate(string $currencyCode): ?string
    {
        $rate = $this->rateApiService->getRateForCurrency($currencyCode);
        
        if (!$rate) {
            return null;
        }
        
        // Use mid rate for revaluation
        return (string) $rate['mid'];
    }

    public function getRevaluationReport(string $date): array
    {
        $entries = RevaluationEntry::where('revaluation_date', $date)
            ->with(['currency', 'postedBy'])
            ->get();
        
        $totalGain = '0';
        $totalLoss = '0';
        
        foreach ($entries as $entry) {
            $amount = $entry->gain_loss_amount;
            if ($this->mathService->compare($amount, '0') >= 0) {
                $totalGain = $this->mathService->add($totalGain, $amount);
            } else {
                $totalLoss = $this->mathService->add($totalLoss, $amount);
            }
        }
        
        return [
            'date' => $date,
            'entries' => $entries,
            'total_gain' => $totalGain,
            'total_loss' => $totalLoss,
            'net_pnl' => $this->mathService->add($totalGain, $totalLoss),
        ];
    }
}
```

- [ ] **Step 2: Create Revaluation Model**
```php
<?php
// app/Models/RevaluationEntry.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevaluationEntry extends Model
{
    protected $fillable = [
        'currency_code', 'till_id', 'old_rate', 'new_rate',
        'position_amount', 'gain_loss_amount', 'revaluation_date', 'posted_by'
    ];

    protected $casts = [
        'old_rate' => 'decimal:6',
        'new_rate' => 'decimal:6',
        'position_amount' => 'decimal:4',
        'gain_loss_amount' => 'decimal:4',
        'revaluation_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
```

- [ ] **Step 3: Create Artisan Command**
```php
<?php
// app/Console/Commands/RunMonthlyRevaluation.php

namespace App\Console\Commands;

use App\Services\RevaluationService;
use Illuminate\Console\Command;

class RunMonthlyRevaluation extends Command
{
    protected $signature = 'revaluation:run {--till=MAIN : Till ID to revalue}';
    protected $description = 'Run monthly currency revaluation';

    public function handle(RevaluationService $service)
    {
        $tillId = $this->option('till');
        
        $this->info("Starting revaluation for till: {$tillId}");
        
        $results = $service->runRevaluation(1, $tillId); // TODO: Get actual user ID
        
        $this->info("Revaluation completed!");
        $this->info("Positions revalued: {$results['positions_revalued']}");
        $this->info("Date: {$results['date']}");
        
        foreach ($results['entries'] as $entry) {
            $sign = $entry['gain_loss'] >= 0 ? '+' : '';
            $this->line("  {$entry['currency']}: {$sign}{$entry['gain_loss']}");
        }
        
        return 0;
    }
}
```

- [ ] **Step 4: Schedule Monthly Revaluation**
Edit `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule): void
{
    // Run revaluation at 23:59 on the last day of each month
    $schedule->command('revaluation:run')
        ->lastDayOfMonth()
        ->at('23:59')
        ->emailOutputTo('accounting@cems.my');
}
```

- [ ] **Step 5: Create Revaluation Tests**
```php
<?php
// tests/Unit/RevaluationServiceTest.php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Services\MathService;
use App\Services\RateApiService;
use App\Services\RevaluationService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RevaluationServiceTest extends TestCase
{
    protected RevaluationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $mathService = new MathService();
        $rateApiService = $this->createMock(RateApiService::class);
        $rateApiService->method('getRateForCurrency')
            ->willReturn(['mid' => 4.70]);
        
        $this->service = new RevaluationService($mathService, $rateApiService);
        
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$']);
    }

    public function test_revalues_position_with_gain()
    {
        // Setup: 1000 USD @ 4.50 cost
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '1000',
            'avg_cost_rate' => '4.50',
            'last_valuation_rate' => '4.50',
        ]);
        
        // New rate: 4.70 (gain of 200)
        $results = $this->service->runRevaluation(1);
        
        $this->assertEquals(1, $results['positions_revalued']);
        $this->assertEquals('200.000000', $results['entries'][0]['gain_loss']);
    }

    public function test_revalues_position_with_loss()
    {
        // Setup: 1000 USD @ 4.90 cost
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '1000',
            'avg_cost_rate' => '4.90',
            'last_valuation_rate' => '4.90',
        ]);
        
        // New rate: 4.70 (loss of 200)
        $results = $this->service->runRevaluation(1);
        
        $this->assertEquals('-200.000000', $results['entries'][0]['gain_loss']);
    }
}
```

- [ ] **Step 6: Run Tests**
```bash
docker-compose exec app php artisan test tests/Unit/RevaluationServiceTest.php
```

Expected: 2 tests, all passing

- [ ] **Step 7: Commit**
```bash
git add app/Services/RevaluationService.php app/Models/RevaluationEntry.php app/Console/Commands/RunMonthlyRevaluation.php app/Console/Kernel.php tests/Unit/RevaluationServiceTest.php
git commit -m "feat: implement automatic monthly revaluation with scheduled command"
```

---

### Task 4.3: Implement Transaction Monitoring Rules

**Files:**
- Create: `app/Services/TransactionMonitoringService.php`
- Create: `app/Listeners/TransactionCreatedListener.php`

- [ ] **Step 1: Create Transaction Monitoring Service**
```php
<?php
// app/Services/TransactionMonitoringService.php

namespace App\Services;

use App\Models\FlaggedTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionMonitoringService
{
    protected ComplianceService $complianceService;
    protected MathService $mathService;

    public function __construct(
        ComplianceService $complianceService,
        MathService $mathService
    ) {
        $this->complianceService = $complianceService;
        $this->mathService = $mathService;
    }

    public function monitorTransaction(Transaction $transaction): array
    {
        $flags = [];
        
        // Rule 1: 24h Velocity Check
        $velocityCheck = $this->complianceService->checkVelocity(
            $transaction->customer_id,
            $transaction->amount_local
        );
        
        if ($velocityCheck['threshold_exceeded']) {
            $flags[] = $this->createFlag($transaction, 'Velocity', 
                "24h velocity exceeded: RM {$velocityCheck['with_new_transaction']}");
        }
        
        // Rule 2: Structuring Detection
        if ($this->complianceService->checkStructuring($transaction->customer_id)) {
            $flags[] = $this->createFlag($transaction, 'Structuring',
                'Potential structuring: 3+ transactions under RM 3,000 within 1 hour');
        }
        
        // Rule 3: Unusual Pattern
        if ($this->isUnusualPattern($transaction)) {
            $flags[] = $this->createFlag($transaction, 'Manual',
                'Transaction deviates 200% from customer average');
        }
        
        // Rule 4: EDD Threshold
        $holdCheck = $this->complianceService->requiresHold(
            $transaction->amount_local,
            $transaction->customer
        );
        
        if ($holdCheck['requires_hold']) {
            $transaction->update(['status' => 'OnHold']);
            
            foreach ($holdCheck['reasons'] as $reason) {
                $flags[] = $this->createFlag($transaction, 'EDD_Required', $reason);
            }
        }
        
        return [
            'transaction_id' => $transaction->id,
            'flags_created' => count($flags),
            'flags' => $flags,
            'status' => $transaction->status,
        ];
    }

    protected function isUnusualPattern(Transaction $transaction): bool
    {
        $customerAvg = Transaction::where('customer_id', $transaction->customer_id)
            ->where('created_at', '>=', now()->subDays(90))
            ->avg('amount_local');
        
        if (!$customerAvg || $customerAvg == 0) {
            return false;
        }
        
        $deviation = $this->mathService->divide(
            $transaction->amount_local,
            (string) $customerAvg
        );
        
        return $this->mathService->compare($deviation, '2') > 0;
    }

    protected function createFlag(Transaction $transaction, string $type, string $reason): FlaggedTransaction
    {
        return FlaggedTransaction::create([
            'transaction_id' => $transaction->id,
            'flag_type' => $type,
            'flag_reason' => $reason,
            'status' => 'Open',
        ]);
    }

    public function getOpenFlags(): array
    {
        return FlaggedTransaction::where('status', 'Open')
            ->with(['transaction.customer', 'assignedTo'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public function assignFlag(int $flagId, int $userId): bool
    {
        return FlaggedTransaction::where('id', $flagId)
            ->update([
                'assigned_to' => $userId,
                'status' => 'Under_Review',
            ]);
    }

    public function resolveFlag(int $flagId, int $userId, ?string $notes = null): bool
    {
        return FlaggedTransaction::where('id', $flagId)
            ->update([
                'reviewed_by' => $userId,
                'notes' => $notes,
                'status' => 'Resolved',
                'resolved_at' => now(),
            ]);
    }
}
```

- [ ] **Step 2: Create Event Listener**
```php
<?php
// app/Listeners/TransactionCreatedListener.php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Services\TransactionMonitoringService;

class TransactionCreatedListener
{
    protected TransactionMonitoringService $monitoringService;

    public function __construct(TransactionMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    public function handle(TransactionCreated $event)
    {
        $this->monitoringService->monitorTransaction($event->transaction);
    }
}
```

- [ ] **Step 3: Create Event**
```php
<?php
// app/Events/TransactionCreated.php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated
{
    use Dispatchable, SerializesModels;

    public Transaction $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
```

- [ ] **Step 4: Register Event Listener**
Edit `app/Providers/EventServiceProvider.php`:
```php
protected $listen = [
    TransactionCreated::class => [
        TransactionCreatedListener::class,
    ],
];
```

- [ ] **Step 5: Commit**
```bash
git add app/Services/TransactionMonitoringService.php app/Listeners/TransactionCreatedListener.php app/Events/TransactionCreated.php app/Providers/EventServiceProvider.php
git commit -m "feat: implement transaction monitoring with automated flagging rules"
```

---

## Phase 5: Reporting & Hardening (Week 5)

### Task 5.1: Implement BNM Reports (LCTR & MSB(2))

**Files:**
- Create: `app/Services/ReportingService.php`
- Create: `app/Http/Controllers/ReportController.php`

- [ ] **Step 1: Create Reporting Service**
```php
<?php
// app/Services/ReportingService.php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportingService
{
    public function generateLCTR(string $month): string
    {
        $startDate = now()->parse($month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        $transactions = Transaction::where('amount_local', '>=', 25000)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['customer', 'user'])
            ->get();
        
        $filename = "LCTR_{$month}.csv";
        $filepath = "reports/{$filename}";
        
        $csv = fopen(Storage::path($filepath), 'w');
        
        // Headers per BNM format
        fputcsv($csv, [
            'Transaction_ID',
            'Date',
            'Time',
            'Customer_ID',
            'Customer_Name',
            'ID_Type',
            'Amount_Local',
            'Amount_Foreign',
            'Currency',
            'Transaction_Type',
            'Branch_ID',
            'Teller_ID',
        ]);
        
        foreach ($transactions as $txn) {
            fputcsv($csv, [
                $txn->id,
                $txn->created_at->format('Y-m-d'),
                $txn->created_at->format('H:i:s'),
                $txn->customer_id,
                $this->maskName($txn->customer->full_name),
                $txn->customer->id_type,
                $txn->amount_local,
                $txn->amount_foreign,
                $txn->currency_code,
                $txn->type,
                'MAIN', // TODO: Use actual branch
                $txn->user_id,
            ]);
        }
        
        fclose($csv);
        
        return $filepath;
    }

    public function generateMSB2(string $date): string
    {
        $queryDate = now()->parse($date);
        
        $summary = DB::table('transactions')
            ->select(
                'currency_code',
                DB::raw("SUM(CASE WHEN type = 'Buy' THEN amount_foreign ELSE 0 END) as buy_volume"),
                DB::raw("SUM(CASE WHEN type = 'Buy' THEN 1 ELSE 0 END) as buy_count"),
                DB::raw("SUM(CASE WHEN type = 'Sell' THEN amount_foreign ELSE 0 END) as sell_volume"),
                DB::raw("SUM(CASE WHEN type = 'Sell' THEN 1 ELSE 0 END) as sell_count")
            )
            ->whereDate('created_at', $queryDate)
            ->groupBy('currency_code')
            ->get();
        
        $filename = "MSB2_{$date}.csv";
        $filepath = "reports/{$filename}";
        
        $csv = fopen(Storage::path($filepath), 'w');
        
        fputcsv($csv, [
            'Date',
            'Currency',
            'Buy_Volume',
            'Buy_Count',
            'Sell_Volume',
            'Sell_Count',
        ]);
        
        foreach ($summary as $row) {
            fputcsv($csv, [
                $date,
                $row->currency_code,
                $row->buy_volume,
                $row->buy_count,
                $row->sell_volume,
                $row->sell_count,
            ]);
        }
        
        fclose($csv);
        
        return $filepath;
    }

    protected function maskName(string $name): string
    {
        $parts = explode(' ', $name);
        $masked = [];
        
        foreach ($parts as $part) {
            if (strlen($part) > 2) {
                $masked[] = substr($part, 0, 2) . str_repeat('*', strlen($part) - 2);
            } else {
                $masked[] = $part;
            }
        }
        
        return implode(' ', $masked);
    }
}
```

- [ ] **Step 2: Create Report Controller**
```php
<?php
// app/Http/Controllers/ReportController.php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    protected ReportingService $reportingService;

    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    public function generateLCTR(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);
        
        $filepath = $this->reportingService->generateLCTR($request->month);
        
        return response()->json([
            'message' => 'LCTR report generated',
            'filename' => basename($filepath),
            'download_url' => url('/reports/download/' . basename($filepath)),
        ]);
    }

    public function generateMSB2(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);
        
        $filepath = $this->reportingService->generateMSB2($request->date);
        
        return response()->json([
            'message' => 'MSB(2) report generated',
            'filename' => basename($filepath),
            'download_url' => url('/reports/download/' . basename($filepath)),
        ]);
    }

    public function download(string $filename)
    {
        $filepath = "reports/{$filename}";
        
        if (!Storage::exists($filepath)) {
            abort(404, 'Report not found');
        }
        
        return Storage::download($filepath);
    }
}
```

- [ ] **Step 3: Add Routes**
Edit `routes/api.php`:
```php
use App\Http\Controllers\ReportController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reports/lctr', [ReportController::class, 'generateLCTR']);
    Route::post('/reports/msb2', [ReportController::class, 'generateMSB2']);
    Route::get('/reports/download/{filename}', [ReportController::class, 'download']);
});
```

- [ ] **Step 4: Commit**
```bash
git add app/Services/ReportingService.php app/Http/Controllers/ReportController.php routes/api.php
git commit -m "feat: implement BNM reporting - LCTR and MSB(2) CSV exports"
```

---

### Task 5.2: Implement Data Breach Detection

**Files:**
- Create: `app/Middleware/DataBreachDetection.php`
- Create: `app/Services/AuditService.php`

- [ ] **Step 1: Create Data Breach Middleware**
```php
<?php
// app/Middleware/DataBreachDetection.php

namespace App\Http\Middleware;

use App\Models\DataBreachAlert;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DataBreachDetection
{
    protected int $threshold = 1000; // records per minute
    protected int $timeWindow = 60; // seconds

    public function handle(Request $request, Closure $next)
    {
        $userId = auth()->id();
        $ipAddress = $request->ip();
        $cacheKey = "data_access:{$userId}:{$ipAddress}";
        
        // Track record access
        $accessCount = Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $accessCount, $this->timeWindow);
        
        // Check threshold
        if ($accessCount > $this->threshold) {
            $this->triggerBreachAlert($userId, $ipAddress, $accessCount);
        }
        
        // Check for mass export
        if ($this->isMassExport($request)) {
            $this->triggerBreachAlert($userId, $ipAddress, 0, 'Export_Anomaly');
        }
        
        return $next($request);
    }

    protected function isMassExport(Request $request): bool
    {
        // Check if request is exporting large dataset
        if ($request->has('export') && $request->has('limit')) {
            return $request->input('limit') > 500;
        }
        
        return false;
    }

    protected function triggerBreachAlert(
        ?int $userId,
        string $ipAddress,
        int $recordCount,
        string $type = 'Mass_Access'
    ): void {
        // Check if alert already exists for this incident
        $existing = DataBreachAlert::where('triggered_by', $userId)
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->where('is_resolved', false)
            ->exists();
        
        if ($existing) {
            return;
        }
        
        DataBreachAlert::create([
            'alert_type' => $type,
            'severity' => 'Critical',
            'description' => "Potential data breach: {$recordCount} PII records accessed in 1 minute",
            'record_count' => $recordCount,
            'triggered_by' => $userId,
            'ip_address' => $ipAddress,
            'is_resolved' => false,
        ]);
        
        // TODO: Send email notification to admin
        // TODO: Optional: Auto-suspend user account
    }
}
```

- [ ] **Step 2: Create Audit Service**
```php
<?php
// app/Services/AuditService.php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function log(
        string $action,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $oldValues = [],
        array $newValues = []
    ): SystemLog {
        return SystemLog::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => !empty($oldValues) ? $oldValues : null,
            'new_values' => !empty($newValues) ? $newValues : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public function logTransaction(
        string $action,
        int $transactionId,
        array $data = []
    ): SystemLog {
        return $this->log(
            $action,
            null,
            'Transaction',
            $transactionId,
            $data['old'] ?? [],
            $data['new'] ?? []
        );
    }

    public function logCustomer(
        string $action,
        int $customerId,
        array $data = []
    ): SystemLog {
        return $this->log(
            $action,
            null,
            'Customer',
            $customerId,
            $data['old'] ?? [],
            $data['new'] ?? []
        );
    }
}
```

- [ ] **Step 3: Register Middleware**
Edit `app/Http/Kernel.php`:
```php
protected $middlewareGroups = [
    'api' => [
        // ... existing middleware
        \App\Http\Middleware\DataBreachDetection::class,
    ],
];
```

- [ ] **Step 4: Commit**
```bash
git add app/Middleware/DataBreachDetection.php app/Services/AuditService.php app/Http/Kernel.php
git commit -m "feat: implement data breach detection with audit logging"
```

---

### Task 5.3: Final Integration & Testing

**Files:**
- Modify: `routes/web.php`
- Create: `database/seeders/DatabaseSeeder.php`
- Create: `phpunit.xml`

- [ ] **Step 1: Create Web Routes**
Edit `routes/web.php`:
```php
<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/compliance', [DashboardController::class, 'compliance'])
        ->name('compliance');
    Route::get('/accounting', [DashboardController::class, 'accounting'])
        ->name('accounting');
    Route::get('/reports', [DashboardController::class, 'reports'])
        ->name('reports');
});

require __DIR__.'/auth.php';
```

- [ ] **Step 2: Create Dashboard Controller**
```php
<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\FlaggedTransaction;
use App\Models\Transaction;
use App\Services\CurrencyPositionService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'today_transactions' => Transaction::whereDate('created_at', today())->count(),
            'today_volume' => Transaction::whereDate('created_at', today())->sum('amount_local'),
            'open_flags' => FlaggedTransaction::where('status', 'Open')->count(),
        ];
        
        return view('dashboard', compact('stats'));
    }

    public function compliance()
    {
        $flags = FlaggedTransaction::where('status', 'Open')
            ->with(['transaction.customer'])
            ->paginate(20);
        
        return view('compliance', compact('flags'));
    }

    public function accounting()
    {
        $service = new CurrencyPositionService(new \App\Services\MathService());
        $positions = $service->getAllPositions();
        $totalPnl = $service->getTotalPnl();
        
        return view('accounting', compact('positions', 'totalPnl'));
    }

    public function reports()
    {
        return view('reports');
    }
}
```

- [ ] **Step 3: Create Database Seeder**
```php
<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('SecurePassword123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        
        // Create test currencies
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$'],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$'],
        ];
        
        foreach ($currencies as $currency) {
            Currency::firstOrCreate(['code' => $currency['code']], $currency);
        }
    }
}
```

- [ ] **Step 4: Run Seeds**
```bash
docker-compose exec app php artisan db:seed
```

- [ ] **Step 5: Final Test Suite**
```bash
docker-compose exec app php artisan test
```

Expected: All tests passing

- [ ] **Step 6: Final Commit**
```bash
git add routes/ app/Http/Controllers/DashboardController.php database/seeders/
git commit -m "feat: complete CEMS-MY implementation with dashboard, seeders, and full test suite"
```

---

## Summary

This implementation plan provides a complete, step-by-step guide to building CEMS-MY:

### Deliverables by Phase:
- **Week 1**: Docker environment, complete MySQL schema (17 tables)
- **Week 2**: Laravel/Slim setup, encryption, BCMath, rate API
- **Week 3**: CDD/EDD, risk rating, sanction screening
- **Week 4**: Currency positions, revaluation, transaction monitoring
- **Week 5**: BNM reports, breach detection, final integration

### Key Features Implemented:
- BNM-compliant CDD/EDD with hard-coded thresholds
- Risk-based customer scoring (automated)
- Real-time currency position tracking with BCMath
- Automatic monthly revaluation
- Transaction monitoring with automated flagging
- LCTR and MSB(2) BNM reports
- Data breach detection (PDPA 2024)
- AES-256 encryption for PII
- Role-based access control with MFA

### Testing:
- Unit tests for all services
- Integration tests for transactions
- PSR-12 coding standards

### Deployment Checklist:
- [ ] SSL/TLS configured
- [ ] Database backups scheduled
- [ ] Environment variables set
- [ ] Staff training completed
- [ ] User manual (EN/BM)

---

**Plan Version:** 1.0  
**Last Updated:** 2025-03-31  
**Estimated Duration:** 5 weeks
