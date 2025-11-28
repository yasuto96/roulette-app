<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // 既存カラムを NULL 許可に変更
            $table->decimal('lat', 10, 7)->nullable()->change();
            $table->decimal('lng', 10, 7)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // 元に戻す（NULL禁止）
            $table->decimal('lat', 10, 7)->nullable(false)->change();
            $table->decimal('lng', 10, 7)->nullable(false)->change();
        });
    }
};
