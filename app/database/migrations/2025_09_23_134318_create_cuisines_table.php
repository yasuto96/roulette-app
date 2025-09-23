<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuisines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();  // ジャンル名は重複禁止
            // $table->softDeletes();               // 必要なら
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuisines');
    }
};
