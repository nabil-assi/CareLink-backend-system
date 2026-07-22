<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('patients', function (Blueprint $table) {
        $table->unsignedBigInteger('guardian_id')->nullable()->after('id');
        $table->foreign('guardian_id')->references('id')->on('patients')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('patients', function (Blueprint $table) {
        $table->dropForeign(['guardian_id']);
        $table->dropColumn('guardian_id');
    });
}
};
