<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_surveys', function (Blueprint $table) {
            $table->string('contract_type')->nullable()->after('street_type');
        });
    }

    public function down(): void
    {
        Schema::table('application_surveys', function (Blueprint $table) {
            $table->dropColumn('contract_type');
        });
    }
};
