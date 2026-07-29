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
        // patient_id كان مربوط بـ foreign key صارم على جدول users بس - يعني
        // مستحيل تقنياً يشتغل لمريض مسجّل من الاستقبال (جدول patients منفصل).
        // بنستبدلها بـ appointment_id ونجيب المريض عن طريق الموعد (نفس أسلوب
        // imaging_orders): appointment->patient بيحل النوعين تلقائياً.
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
            $table->foreignId('appointment_id')->nullable()->after('id')->constrained('appointments')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropColumn('appointment_id');
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
        });
    }
};
