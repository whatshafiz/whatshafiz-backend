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
        Schema::table('courses', function (Blueprint $table) {
            $table->timestamp('students_matchings_started_at')->nullable()->after('can_be_applied_until');
            $table->timestamp('proficiency_exam_start_time')->nullable()->after('can_be_applied_until');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['students_matchings_started_at', 'proficiency_exam_start_time']);
        });
    }
};
