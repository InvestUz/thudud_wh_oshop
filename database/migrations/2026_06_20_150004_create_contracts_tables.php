<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tasdiqlangan ariza asosida tuzilgan shartnoma.
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('object_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->date('contract_date');
            $table->decimal('total_amount', 14, 2);
            $table->decimal('monthly_amount', 14, 2);
            $table->decimal('penalty_rate', 5, 2)->default(0.1); // kunlik penya foizi
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active'); // ContractStatus
            $table->string('control_status')->nullable();
            $table->text('problem_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'district_id']);
        });

        // Shartnoma nazorat harakatlari (to'xtatish / tiklash / bekor qilish / ko'rish).
        Schema::create('contract_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action'); // ContractActionType
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // 12 oylik to'lov grafigi.
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month_no'); // 1..12
            $table->string('period'); // YYYY-MM
            $table->date('due_date');
            $table->decimal('amount', 14, 2);
            $table->decimal('penalty_amount', 14, 2)->default(0);
            $table->string('status')->default('pending'); // PaymentStatus
            $table->timestamps();
        });

        // Hisob-fakturalar (har bir to'lov grafigi uchun).
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_schedule_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('due_date');
            $table->string('status')->default('pending'); // InvoiceStatus
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payment_schedules');
        Schema::dropIfExists('contract_actions');
        Schema::dropIfExists('contracts');
    }
};
