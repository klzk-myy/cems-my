<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('deferred_journal_entry_id')
                ->nullable()
                ->after('journal_entry_id')
                ->references('id')
                ->on('journal_entries')
                ->nullOnDelete();
            
            $table->timestamp('journal_entries_created_at')
                ->nullable()
                ->after('deferred_journal_entry_id')
                ->comment('When journal entries were actually created (for deferred Enhanced CDD)');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['deferred_journal_entry_id']);
            $table->dropColumn(['deferred_journal_entry_id', 'journal_entries_created_at']);
        });
    }
};
