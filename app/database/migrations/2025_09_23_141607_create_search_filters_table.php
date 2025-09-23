<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSearchFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('search_filters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                 ->constrained('members')
                  ->cascadeOnDelete();

            $table->string('name', 50);      // ユーザーが付けるラベル
            $table->json('criteria')->nullable(); // 条件本体

           $table->timestamps();

            // 同一ユーザー内で同名をユニークに（任意）
            $table->unique(['member_id','name']);
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
