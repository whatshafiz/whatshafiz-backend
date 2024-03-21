<?php

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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CourseType::class)->nullable()->constrained();
            $table->string('name', 100);
            $table->boolean('is_active')->default(false);
            $table->datetime('start_at')->nullable();
            $table->boolean('can_be_applied')->default(false);
            $table->datetime('can_be_applied_until')->nullable();
            $table->timestamp('students_matchings_started_at')->nullable();
            $table->timestamp('proficiency_exam_start_time')->nullable();
            $table->string('register_url')->nullable();
            $table->string('whatsapp_channel_join_url')->nullable();
            $table->foreignId('admission_exam_id')->nullable()->constrained('admission_exams');

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
