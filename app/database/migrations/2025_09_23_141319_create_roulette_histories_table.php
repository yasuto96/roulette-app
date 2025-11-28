// database/migrations/2025_10_09_150000_add_fields_to_roulette_histories.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roulette_histories', function (Blueprint $table) {
            // 行ってみるを押した日（作成時に now() を入れる）
            $table->dateTime('visited_at')->nullable()->after('criteria');
            // 自分の評価(1〜5)
            $table->unsignedTinyInteger('my_rating')->nullable()->after('visited_at');
            // コメント
            $table->text('memo')->nullable()->after('my_rating');
            // 店舗ID（お気に入り連携に使う）
            $table->foreignId('restaurant_id')->nullable()->after('member_id')
                ->constrained('restaurants')->nullOnDelete();
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
