<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Noturar (tijorat) ob'ektlar — doimiy ma'lumot.
        Schema::create('objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('cadastre_number')->unique();
            $table->string('hokimiyat_cadastre')->nullable();
            $table->string('tin_pinfl')->nullable();
            $table->string('company_name');
            $table->string('director_name')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mahalla_id')->nullable()->constrained()->nullOnDelete();
            $table->string('street')->nullable();
            $table->string('street_status')->nullable();
            $table->string('house_number')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Ijarachilar (bir ob'ektda bir nechta bo'lishi mumkin).
        Schema::create('object_tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained()->cascadeOnDelete();
            $table->string('tin_pinfl')->nullable();
            $table->string('name');
            $table->string('activity_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('object_tenants');
        Schema::dropIfExists('objects');
    }
};
