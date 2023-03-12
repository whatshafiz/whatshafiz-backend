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
        Schema::create('exam_user_assignment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('quran_exams');
            $table->foreignId('user_id')->constrained('users');
            $table->int('answer')->default(null);
            $table->boolean('is_success')->default(null);
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
        Schema::dropIfExists('exam_user_assignment');
    }
};
