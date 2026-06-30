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
        Schema::table('charts', function (Blueprint $table) {
            $table->unsignedInteger('header_row')->default(1)->after('spreadsheet_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charts', function (Blueprint $table) {
            $table->dropColumn('header_row');
        });
    }
};
