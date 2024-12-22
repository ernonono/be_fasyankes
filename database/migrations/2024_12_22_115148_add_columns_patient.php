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
            $table->string('image')->nullable();
            $table->string('status')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('related_contact')->nullable();
            $table->string('bpjs')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('image');
            $table->dropColumn('status');
            $table->dropColumn('father_name');
            $table->dropColumn('mother_name');
            $table->dropColumn('related_contact');
            $table->dropColumn('bpjs');
        });
    }
};
