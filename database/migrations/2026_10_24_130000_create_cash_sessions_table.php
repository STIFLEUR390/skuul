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
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('opened_at');
            $table->decimal('opening_float', 15, 2)->default(0);
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->decimal('counted_amount', 15, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'closed_at']);
            $table->index(['school_id', 'opened_by', 'closed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
