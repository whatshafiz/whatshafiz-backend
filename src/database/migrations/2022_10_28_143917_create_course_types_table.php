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
        Schema::create('course_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('course_types');
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->boolean('is_active')->default(true);
            $table->boolean('has_admission_exam')->default(true);
            $table->tinyInteger('min_age')->nullable();
            $table->json('genders')->nullable();
            $table->json('education_levels')->nullable();

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
        Schema::dropIfExists('course_types');
    }
};
