<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuestSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
{
    Schema::create('guest_sessions', function (Blueprint $table) {
        $table->id();

        // ブラウザCookieなどに入れる一意トークン
        $table->string('token', 64)->unique();

        // ひも付けたい任意データをJSONで（直近検索や仮お気に入りなど）
        $table->json('payload')->nullable();

        // 有効期限の目安（任意）
        $table->timestamp('expires_at')->nullable();

        $table->timestamps();

        // 有効期限で掃除するなら index
        // $table->index('expires_at');
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('guest_sessions');
    }
}
