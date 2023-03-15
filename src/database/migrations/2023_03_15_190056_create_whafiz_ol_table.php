<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hafiz_ol', function (Blueprint $table) {
            $table->id();
            $table->integer('day');
            $table->json('main_pages');
            $table->json('sub_pages');
            $table->foreignId('course_id')->constrained('courses');
            $table->boolean('is_teacher')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hafiz_ol');
    }
};
