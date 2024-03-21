<?php

use App\Models\Course;
use App\Models\CourseType;
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
        Schema::create('whatsapp_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Course::class)->constrained();
            $table->foreignIdFor(CourseType::class)->nullable()->constrained();
            $table->enum('gender', ['male', 'female']);
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->string('join_url')->nullable();
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
        Schema::dropIfExists('whatsapp_groups');
    }
};
