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
        Schema::table('patients', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
            $table->string('gender')->nullable()->change();
            $table->string('birth')->nullable()->change();
            $table->string('address')->nullable()->change();
            $table->string('religion')->nullable()->change();
            $table->string('nik')->nullable()->change();
            $table->string('kk')->nullable()->change();
            $table->string('blood_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
            $table->string('gender')->nullable(false)->change();
            $table->string('birth')->nullable(false)->change();
            $table->string('address')->nullable(false)->change();
            $table->string('religion')->nullable(false)->change();
            $table->string('nik')->nullable(false)->change();
            $table->string('kk')->nullable(false)->change();
            $table->string('blood_type')->nullable(false)->change();
        });
    }
};
