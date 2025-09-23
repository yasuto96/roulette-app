<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouletteHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up(): void
    {
        Schema::create('roulette_histories', function (Blueprint $table) {
            $table->id();

            // 誰の履歴か（会員に紐づけ）
            $table->foreignId('member_id')
                  ->constrained('members')
                  ->cascadeOnDelete();

            // ユーザーが付ける任意の名前（同一ユーザー内で一意にしたい場合は unique を複合で）
            $table->string('name', 50);

            // 抽選条件（距離、予算、ジャンル配列など柔軟に持てる）
            $table->json('criteria');

            $table->timestamps();

            // 同一ユーザー内で同名を禁止したい場合（任意）
            $table->unique(['member_id', 'name']);
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
