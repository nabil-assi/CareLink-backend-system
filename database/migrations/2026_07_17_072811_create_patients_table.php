<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // الجدول ممكن يكون انعمل مسبقاً بمحاولة ديبلوي سابقة فشلت بعد إنشائه
        // وقبل ما يتسجل بجدول migrations - هيك منتجنب "table already exists"
        if (Schema::hasTable('patients')) {
            return;
        }

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('national_id')->nullable()->unique();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};