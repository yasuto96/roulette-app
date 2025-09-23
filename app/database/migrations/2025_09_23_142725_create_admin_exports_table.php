<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminExportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
{
    Schema::create('admin_exports', function (Blueprint $table) {
        $table->id();

        // 誰が実行したか（adminsに外部キー）
        $table->foreignId('admin_id')
              ->constrained('admins')
              ->cascadeOnDelete();

        $table->string('type', 50);              // 出力種別: restaurants / members など
        $table->json('criteria')->nullable();    // 出力条件(JSON)
        $table->string('file_path', 255);        // 保存先パス（storage相対やS3キー）
        $table->string('status', 20)->default('done'); // done / failed / running など

        $table->timestamps();

        // よく絞り込むなら必要に応じて
        // $table->index(['type','status']);
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
