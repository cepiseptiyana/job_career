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
        // tambah kolom di table jobs
        Schema::table('jobs', function (Blueprint $table) {
            $table->integer('status')->after('company_website')->default(1);
            $table->integer('isFeatured')->after('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // hapus kolom table jobs
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('isFeatured');
        });
    }
};
