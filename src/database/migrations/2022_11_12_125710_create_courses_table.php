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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['whatshafiz', 'whatsenglish', 'whatsarapp']);
            $table->string('name', 100);
            $table->boolean('is_active')->default(false);
            $table->boolean('can_be_applied')->default(false);
            $table->datetime('can_be_applied_until')->nullable();
            $table->datetime('start_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses');
    }
};
