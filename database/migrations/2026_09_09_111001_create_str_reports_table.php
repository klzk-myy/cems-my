<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('str_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id')->nullable(); // Originating compliance case (optional)
            $table->unsignedBigInteger('customer_id');
            $table->decimal('trigger_amount', 18, 4)->default(0); // MYR aggregate that tripped pd-00 s22
            $table->text('trigger_reason');
            $table->string('status', 20)->default('Draft'); // StrReportStatus enum: Draft/Submitted/Acknowledged/Rejected
            $table->string('bnm_reference')->nullable(); // Reference issued when lodged with BNM FIED
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'str_reports_status_idx');
            $table->index('customer_id', 'str_reports_customer_id_idx');

            // Foreign keys follow the compliance_cases convention: restrict on
            // delete so regulatory records cannot be orphaned silently. The
            // case link is nullable, so a hard case deletion detaches rather
            // than destroys the report.
            $table->foreign('case_id')->references('id')->on('compliance_cases')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('str_reports');
    }
};
