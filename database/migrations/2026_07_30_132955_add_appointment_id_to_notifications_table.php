<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // عشان الإشعار يقدر يوديك لمكانه الصح (الموعد يلي فيه الشات
            // أو نتيجة التحليل) بدل ما يبس يتعلّم مقروء وما يوديك لأي مكان
            $table->foreignId('appointment_id')->nullable()->after('body')
                ->constrained('appointments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('appointment_id');
        });
    }
};
