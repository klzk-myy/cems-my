<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // MySQL: use renameColumn for efficiency
            Schema::table('aml_rules', function (Blueprint $table) {
                $table->renameColumn('is_enabled', 'is_active');
            });

            Schema::table('aml_rules', function (Blueprint $table) {
                $table->renameColumn('parameters', 'conditions');
            });

            Schema::table('aml_rules', function (Blueprint $table) {
                $table->string('rule_type', 50)->nullable();
                $table->string('action', 20)->default('flag');
                $table->integer('risk_score')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dropColumn(['priority', 'flag_type']);
                $table->index('rule_type');
                $table->index('action');
                $table->index('risk_score');
            });
        } else {
            // SQLite: recreate table (renameColumn not supported)
            $this->recreateTableForSqlite();
        }
    }

    /**
     * Recreate aml_rules table for SQLite compatibility.
     */
    protected function recreateTableForSqlite(): void
    {
        // Get existing data
        $existingData = DB::table('aml_rules')->get();

        // Drop existing table
        Schema::dropIfExists('aml_rules');

        // Create new table with correct structure
        Schema::create('aml_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_code', 50)->unique();
            $table->string('rule_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable();
            $table->string('rule_type', 50)->nullable();
            $table->string('action', 20)->default('flag');
            $table->integer('risk_score')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index('is_active');
            $table->index('rule_type');
            $table->index('action');
            $table->index('risk_score');
        });

        // Restore data (map old column names to new ones)
        foreach ($existingData as $row) {
            DB::table('aml_rules')->insert([
                'id' => $row->id,
                'rule_code' => $row->rule_code,
                'rule_name' => $row->rule_name,
                'description' => $row->description,
                'is_active' => $row->is_enabled ?? true,
                'conditions' => $row->parameters ?? null,
                'rule_type' => null,
                'action' => 'flag',
                'risk_score' => 0,
                'created_by' => null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('aml_rules', function (Blueprint $table) {
                $table->dropIndex(['rule_type']);
                $table->dropIndex(['action']);
                $table->dropIndex(['risk_score']);
                $table->dropColumn(['rule_type', 'action', 'risk_score', 'created_by']);
                $table->integer('priority')->default(100);
                $table->string('flag_type', 50);
            });

            Schema::table('aml_rules', function (Blueprint $table) {
                $table->renameColumn('conditions', 'parameters');
            });

            Schema::table('aml_rules', function (Blueprint $table) {
                $table->renameColumn('is_active', 'is_enabled');
            });
        } else {
            // SQLite: reverse the recreation
            $this->reverseRecreationForSqlite();
        }
    }

    /**
     * Reverse the SQLite table recreation.
     */
    protected function reverseRecreationForSqlite(): void
    {
        // Get existing data
        $existingData = DB::table('aml_rules')->get();

        // Drop existing table
        Schema::dropIfExists('aml_rules');

        // Create original table structure
        Schema::create('aml_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_code', 50)->unique();
            $table->string('rule_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->string('flag_type', 50);
            $table->json('parameters')->nullable();
            $table->integer('priority')->default(100);
            $table->timestamps();
            $table->index('is_enabled');
            $table->index('priority');
        });

        // Restore data (map new column names to old ones)
        foreach ($existingData as $row) {
            DB::table('aml_rules')->insert([
                'id' => $row->id,
                'rule_code' => $row->rule_code,
                'rule_name' => $row->rule_name,
                'description' => $row->description,
                'is_enabled' => $row->is_active ?? true,
                'flag_type' => null,
                'parameters' => $row->conditions ?? null,
                'priority' => 100,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }
    }
};
