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
        Schema::create('user_course', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['whatshafiz', 'whatsenglish', 'whatsarapp']);
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(Course::class)->constrained();
            $table->boolean('is_teacher')->default(false);
            $table->timestamp('applied_at')->useCurrent();
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
