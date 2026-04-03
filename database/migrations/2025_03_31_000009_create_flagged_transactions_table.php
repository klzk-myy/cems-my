<?php

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
