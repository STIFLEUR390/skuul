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
        Schema::table('course_offerings', function (Blueprint $table): void {
            // The weight a subject carries on the report card, as schools
            // print it: maths at 4, a minor subject at 1.
            $table->decimal('coefficient', 4, 2)->default(1)->after('capacity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_offerings', function (Blueprint $table): void {
            $table->dropColumn('coefficient');
        });
    }
};
