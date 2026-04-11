<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make posted_by and posted_at nullable in journal_entries.
     *
     * This is needed because entries are now created in Draft status
     * and posted_by/posted_at are only set when the entry is approved and posted.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE journal_entries MODIFY COLUMN posted_by BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE journal_entries MODIFY COLUMN posted_at TIMESTAMP NULL");
    }

    /**
     * Reverse the changes.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE journal_entries MODIFY COLUMN posted_by BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE journal_entries MODIFY COLUMN posted_at TIMESTAMP NOT NULL");
    }
};
