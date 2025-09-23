<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();                                  // 店舗ID

            // 外部ソース情報（例: google / hotpepper など）
            $table->string('source', 20);                  // 取得元
            $table->string('source_id', 100);              // 取得元側のID
            $table->unique(['source', 'source_id']);       // 同じソース内で一意

            // 基本情報
            $table->string('name', 255);                   // 店名
            $table->string('address', 255)->nullable();    // 住所
            $table->string('phone', 30)->nullable();       // 電話
            $table->string('website', 255)->nullable();    // 公式/詳細URL

            // 位置情報：範囲検索を考慮してdecimal
            $table->decimal('lat', 10, 7);                 // 緯度
            $table->decimal('lng', 10, 7);                 // 経度
            $table->index(['lat','lng']);                  // 位置検索の高速化

            // 価格帯 / レーティング（仕様に合わせて任意）
            $table->tinyInteger('price_level')->nullable(); // 0–4 など
            $table->decimal('rating', 2, 1)->nullable();    // 例: 4.3
            $table->integer('rating_total')->nullable();    // 口コミ数

            // 営業情報など可変が大きいものはJSONで柔軟に
            $table->json('opening_hours')->nullable();

            // 論理削除を使いたい場合はコメントアウト解除
            // $table->softDeletes();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
