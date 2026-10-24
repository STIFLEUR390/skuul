<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_payments', function (Blueprint $table) {
            $table->foreignId('cash_session_id')->nullable()->after('financial_period_id')
                ->constrained('cash_sessions')->nullOnDelete();
            $table->unsignedBigInteger('receipt_number')->nullable()->after('cash_session_id');

            $table->unique(['school_id', 'receipt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_payments', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'receipt_number']);
            $table->dropConstrainedForeignId('cash_session_id');
            $table->dropColumn('receipt_number');
        });
    }
};
