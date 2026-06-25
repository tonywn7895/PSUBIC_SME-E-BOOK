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
        Schema::table('ebooks', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->string('province')->nullable();
            $table->unsignedSmallInteger('fiscal_year')->nullable();

            $table->index('category');
            $table->index('province');
            $table->index('fiscal_year');
        });

        Schema::table('spreadsheets', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->string('province')->nullable();
            $table->unsignedSmallInteger('fiscal_year')->nullable();

            $table->index('category');
            $table->index('province');
            $table->index('fiscal_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebooks', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['province']);
            $table->dropIndex(['fiscal_year']);

            $table->dropColumn(['category', 'province', 'fiscal_year']);
        });

        Schema::table('spreadsheets', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['province']);
            $table->dropIndex(['fiscal_year']);

            $table->dropColumn(['category', 'province', 'fiscal_year']);
        });
    }
};
