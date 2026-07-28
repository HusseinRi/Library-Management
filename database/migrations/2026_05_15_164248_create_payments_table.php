<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->integer('amount'); // المبلغ بالسنتات
            $table->string('stripe_payment_intent_id')->nullable()->unique(); // تم إضافة معرف سترايب الفريد هنا
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending'); // تم إضافة pending كحالة افتراضية
            $table->timestamp('paid_at')->nullable(); // تم جعله nullable وتغييره لـ timestamp لتسجيل الوقت بدقة ثانية بدلاً من اليوم فقط
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
