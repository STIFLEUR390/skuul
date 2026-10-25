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
        Schema::table('academic_levels', function (Blueprint $table): void {
            $table->string('assessment_mode', 20)->default('marks')->after('position');
        });

        Schema::create('competency_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_level_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['school_id', 'academic_level_id', 'position']);
        });

        Schema::create('competencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competency_domain_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('competency_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_period_id')->constrained()->cascadeOnDelete();
            $table->string('level', 20);
            $table->text('comment')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_record_id', 'competency_id', 'academic_period_id'], 'competency_assessments_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competency_assessments');
        Schema::dropIfExists('competencies');
        Schema::dropIfExists('competency_domains');

        Schema::table('academic_levels', function (Blueprint $table): void {
            $table->dropColumn('assessment_mode');
        });
    }
};
