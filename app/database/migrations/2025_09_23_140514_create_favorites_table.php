<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFavoritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up(): void
{
    Schema::create('favorites', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('member_id');
        $table->unsignedBigInteger('restaurant_id');
        $table->timestamps();

        // 外部キー制約
        $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');

        // 同じ組み合わせを重複登録させないためのユニーク制約
        $table->unique(['member_id', 'restaurant_id']);
    });
}



    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admin_exports');
    }
}

