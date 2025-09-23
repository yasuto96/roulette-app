<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_cuisines', function (Blueprint $table) {
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('cuisine_id');

            $table->primary(['restaurant_id','cuisine_id']);

            $table->foreign('restaurant_id')
                  ->references('id')->on('restaurants')
                  ->cascadeOnDelete();

            $table->foreign('cuisine_id')
                  ->references('id')->on('cuisines')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_cuisines');
    }
};
