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
        Schema::table('doctors', function (Blueprint $table) {
            $table->string('nik')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->text('address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('hospital_code')->nullable();
            $table->string('profession')->nullable();
            $table->string('unique_number')->nullable();
            $table->string('facebook_link')->nullable();
            $table->string('twitter_link')->nullable();
            $table->string('google_plus_link')->nullable();
            $table->string('linkedin_link')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn('nik');
            $table->dropColumn('birthdate');
            $table->dropColumn('gender');
            $table->dropColumn('address');
            $table->dropColumn('phone_number');
            $table->dropColumn('hospital_code');
            $table->dropColumn('profession');
            $table->dropColumn('unique_number');
            $table->dropColumn('facebook_link');
            $table->dropColumn('twitter_link');
            $table->dropColumn('google_plus_link');
            $table->dropColumn('linkedin_link');
        });
    }
};
