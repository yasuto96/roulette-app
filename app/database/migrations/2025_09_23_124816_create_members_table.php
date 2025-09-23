<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** 
     * 目的：
     * - 会員（ログインできる一般ユーザー）を格納
     * - Breezeのusers相当だが、役割分離のため members として独立させる
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            // 主キー：Auto IncrementのBIGINT
            $table->id();

            // 表示名（必須）
            $table->string('name', 255);

            // 認証用メール：重複禁止（DBでuniqe制約を張るのは超重要）
            $table->string('email', 255)->unique();

            // ハッシュ済みパスワード（Bcrypt/Argonを想定。生パスは絶対保存しない）
            $table->string('password', 255);

            // メール認証の完了日時（未認証ならNULL）
            $table->timestamp('email_verified_at')->nullable();

            // 「ログイン状態を保持する」トークン
            $table->rememberToken();

            // 作成・更新時刻
            $table->timestamps();

            // よく検索するなら index を追加する例（任意）
            // $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
