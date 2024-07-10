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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('registration_id');
            $table->unsignedBigInteger('medical_record_id');
            $table->string('name');
            $table->string('phone');
            $table->string('gender');
            $table->date('birth');
            $table->string('address');
            $table->string('status');
            $table->string('religion');
            $table->string('nik');
            $table->string('kk');
            $table->string('blood_type');
            $table->string('parents');
            $table->text('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasiens');
    }
};
