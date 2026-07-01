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
       Schema::create('favorites', function (Blueprint $table) {
        $table->id();
        // ربط معرف المستخدم
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        // ربط معرف الكتاب
        $table->foreignId('book_id')->constrained()->onDelete('cascade');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
