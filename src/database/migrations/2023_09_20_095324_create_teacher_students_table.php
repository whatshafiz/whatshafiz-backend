<?php

use App\Models\Course;
use App\Models\User;
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
        Schema::create('teacher_students', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Course::class, 'course_id')->constrained();
            $table->foreignIdFor(User::class, 'teacher_id')->constrained('users');
            $table->foreignIdFor(User::class, 'student_id')->constrained('users');
            $table->boolean('is_active')->default(true);
            $table->boolean('proficiency_exam_passed')->nullable();
            $table->string('proficiency_exam_failed_description')->nullable();
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
        Schema::dropIfExists('teacher_students');
    }
};
