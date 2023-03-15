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
        Schema::create('quran_page_checks', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_correct')->nullable();
            $table->boolean('is_checked')->default(false);
            $table->boolean('is_main')->default(false);
            $table->integer('page_number');
            $table->foreignId('hafiz_attempt_id')->constrained('hafiz_attempts');
            $table->timestamp('checked_at')->nullable();
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
        Schema::dropIfExists('quran_page_checks');
    }
};
