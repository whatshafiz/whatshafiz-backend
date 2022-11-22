<?php

use App\Models\University;
use App\Models\UniversityFaculty;
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
        Schema::create('university_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(University::class)->constrained();
            $table->foreignIdFor(UniversityFaculty::class)->constrained();
            $table->string('name');
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
        Schema::dropIfExists('university_departments');
    }
};
