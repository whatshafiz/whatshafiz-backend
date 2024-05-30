<?php

use App\Models\Course;
use App\Models\CourseType;
use App\Models\User;
use App\Models\WhatsappGroup;
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
        Schema::create('user_course', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(Course::class)->constrained();
            $table->foreignIdFor(CourseType::class)->constrained();
            $table->foreignIdFor(WhatsappGroup::class)->nullable()->constrained();
            $table->boolean('is_teacher')->default(false);
            $table->boolean('is_moderator')->default(false);
            $table->timestamp('moderation_started_at')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('removed_at')->nullable();

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
        Schema::dropIfExists('user_course');
    }
};
