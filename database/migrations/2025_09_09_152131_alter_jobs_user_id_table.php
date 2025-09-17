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
            $table->foreignId('user_id')->after('job_type_id')->constrained('users', 'id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('jobs', function (Blueprint $table) {
            // putus hubungan foregin ke references 
            $table->dropForeign(['user_id']);
            // baru delete kolom
            $table->dropColumn('user_id');
        });
    }
};
