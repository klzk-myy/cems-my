<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix mfa_secret column size.
     *
     * The column was varchar(32) but Crypt::encryptString() produces
     * base64 strings longer than 32 characters.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users MODIFY COLUMN mfa_secret TEXT');
    }

    /**
     * Reverse the change.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users MODIFY COLUMN mfa_secret VARCHAR(32)');
    }
};
