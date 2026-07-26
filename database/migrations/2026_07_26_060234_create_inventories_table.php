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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('company')->nullable();

            // مجموع الكمية عبر كل الدفعات (يُعاد حسابه تلقائياً)
            $table->integer('quantity')->default(0);
            $table->integer('min_quantity')->default(10);
            $table->string('unit')->default('علبة');
            $table->decimal('price', 10, 2)->default(0);

            // أقرب تاريخ صلاحية بين الدفعات (يُعاد حسابه تلقائياً)
            $table->date('expiry_date')->nullable();

            // كلمات بحث إضافية، تُخزَّن كمصفوفة JSON
            $table->text('keywords')->nullable();

            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
