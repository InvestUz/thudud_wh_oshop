<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tutash hududdan foydalanish arizalari.
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->unique();
            $table->foreignId('object_id')->constrained()->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('draft');         // ApplicationStatus
            $table->string('current_stage')->default('draft');  // ApplicationStage
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->text('reject_reason')->nullable();
            $table->string('draft_document_path')->nullable(); // mas'ul xodim uzatganda yaratilgan shartnoma loyihasi (docx)
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['current_stage', 'district_id']);
            $table->index(['status', 'district_id']);
        });

        // O'lchov / joy o'rganish (mas'ul xodim to'ldiradi).
        Schema::create('application_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('surveyed_by')->constrained('users')->cascadeOnDelete();
            $table->string('stage')->nullable();
            $table->decimal('length_m', 10, 2)->nullable();
            $table->decimal('width_m', 10, 2)->nullable();
            $table->decimal('calculated_area', 12, 2)->nullable();
            $table->decimal('total_area', 12, 2)->nullable();
            $table->string('calc_method')->nullable();
            $table->decimal('facade_length_m', 10, 2)->nullable();
            $table->string('terrace_sides')->nullable();
            $table->string('street_type')->nullable(); // Кўча тури: оддий / гастрономик
            $table->decimal('distance_to_road_m', 10, 2)->nullable();
            $table->decimal('distance_to_sidewalk_m', 10, 2)->nullable();
            $table->string('usage_purpose')->nullable();
            $table->string('activity_type')->nullable();
            $table->string('terrace_structures')->nullable();
            $table->string('permanent_structures')->nullable();
            $table->string('permit')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('extra_info')->nullable();
            $table->json('photos')->nullable();    // yuklangan rasmlar yo'llari
            $table->json('documents')->nullable(); // "Kerakli hujjatlar" — yuklangan fayllar yo'llari
            $table->json('geo_area')->nullable();   // xaritada belgilangan maydon (GeoJSON)
            $table->json('data')->nullable();
            $table->timestamps();
        });

        // Tutash hudud ma'lumoti (ariza bilan birga kiritiladi).
        Schema::create('adjacent_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('activity')->nullable();
            $table->decimal('area_m2', 12, 2);
            $table->string('structures')->nullable();
            $table->timestamps();
        });

        // Pipeline tarixi + audit jurnali.
        Schema::create('application_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('from_stage')->nullable();
            $table->string('to_stage');
            $table->string('action'); // TransitionAction
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_transitions');
        Schema::dropIfExists('adjacent_areas');
        Schema::dropIfExists('application_surveys');
        Schema::dropIfExists('applications');
    }
};
