<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('application_surveys', function (Blueprint $table) {
            $table->string('study_report_path')->nullable()->after('documents');
        });

        Schema::create('application_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('role');
            $table->string('decision');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['application_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_reviews');
        Schema::table('application_surveys', fn (Blueprint $table) => $table->dropColumn('study_report_path'));
    }
};
