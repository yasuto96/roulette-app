<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roulette_histories', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->nullable()->after('member_id')
                  ->constrained('restaurants')->nullOnDelete();
            $table->dateTime('visited_at')->nullable()->after('criteria');
            $table->unsignedTinyInteger('my_rating')->nullable()->after('visited_at');
            $table->text('memo')->nullable()->after('my_rating');
        });
    }

    public function down(): void
    {
        Schema::table('roulette_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restaurant_id');
            $table->dropColumn(['visited_at','my_rating','memo']);
        });
    }
};
