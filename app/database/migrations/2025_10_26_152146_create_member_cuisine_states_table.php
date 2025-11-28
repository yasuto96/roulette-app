<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('member_cuisine_states', function (Blueprint $table) {
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('cuisine_id');
            $table->boolean('is_checked')->default(true); // ON/OFF を明示保存
            $table->timestamps();

            $table->primary(['member_id','cuisine_id']);
            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            $table->foreign('cuisine_id')->references('id')->on('cuisines')->cascadeOnDelete();
        });
    }
    public function down(): void {
        Schema::dropIfExists('member_cuisine_states');
    }
};
