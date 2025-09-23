<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
          $table->id(); // 主キー

          // 管理者名（必須）
          $table->string('name', 255);

           // 管理者用メールアドレス（重複不可）
           $table->string('email', 255)->unique();

           // パスワード（bcrypt/argonでハッシュ化）
           $table->string('password', 255);

           // ログイン状態保持用トークン
           $table->rememberToken();

            // 作成・更新日時
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
}