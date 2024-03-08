<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->string('name');
            $table->string('type');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('course_types')
            ->insert([
                ['name' => 'WhatsHafiz', 'type' => 'whatshafiz'],
                ['name' => 'WhatsEnglish', 'type' => 'whatsenglish'],
                ['name' => 'WhatsArapp', 'type' => 'whatsarapp'],
            ]);
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
