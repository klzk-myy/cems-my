<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enforce uniqueness of KYC blind hashes.
 *
 * id_number_hash / phone_hash previously had plain indexes only, so concurrent
 * registrations could create duplicate customer identities and split AML
 * aggregation across rows. Nullable columns keep multiple NULLs under a unique
 * index on both MySQL and SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $schema = DB::connection()->getSchemaBuilder();

            if ($schema->hasIndex('customers', 'customers_id_number_hash_index')) {
                $table->dropIndex('customers_id_number_hash_index');
            }

            if (! $schema->hasIndex('customers', 'customers_id_number_hash_unique')) {
                $table->unique('id_number_hash', 'customers_id_number_hash_unique');
            }

            if ($schema->hasIndex('customers', 'customers_phone_hash_index')) {
                $table->dropIndex('customers_phone_hash_index');
            }

            if (! $schema->hasIndex('customers', 'customers_phone_hash_unique')) {
                $table->unique('phone_hash', 'customers_phone_hash_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $schema = DB::connection()->getSchemaBuilder();

            if ($schema->hasIndex('customers', 'customers_phone_hash_unique')) {
                $table->dropUnique('customers_phone_hash_unique');
            }

            if (! $schema->hasIndex('customers', 'customers_phone_hash_index')) {
                $table->index('phone_hash');
            }

            if ($schema->hasIndex('customers', 'customers_id_number_hash_unique')) {
                $table->dropUnique('customers_id_number_hash_unique');
            }

            if (! $schema->hasIndex('customers', 'customers_id_number_hash_index')) {
                $table->index('id_number_hash');
            }
        });
    }
};
