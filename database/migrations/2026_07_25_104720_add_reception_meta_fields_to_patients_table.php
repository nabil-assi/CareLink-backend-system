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
        // مودال "تنبيه/تأمين" بالاستقبال (ReceptionPatientMetaModal) كان جاهز بالفرونت
        // بس هالأعمدة ما كانت موجودة أصلاً بجدول patients، فكان بيشتغل على بيانات وهمية بس
        Schema::table('patients', function (Blueprint $table) {
            $table->string('insurance_status')->default('unknown')->after('address');
            $table->string('insurance_provider')->nullable()->after('insurance_status');
            $table->json('reception_flags')->nullable()->after('insurance_provider');
            $table->text('reception_note')->nullable()->after('reception_flags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['insurance_status', 'insurance_provider', 'reception_flags', 'reception_note']);
        });
    }
};
