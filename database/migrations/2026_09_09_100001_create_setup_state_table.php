<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistent, immutable marker that initial application setup has completed.
 *
 * The data-derived heuristic (users + currencies + exchange rates + branches
 * non-empty) can be defeated by emptying a table, which re-opens the setup
 * takeover path. Once SetupService marks this row, the setup wizard is closed
 * in every environment until an admin runs the non-production reset.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('setup_state')) {
            Schema::create('setup_state', function (Blueprint $table) {
                $table->id();
                $table->timestamp('setup_completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('setup_state');
    }
};
