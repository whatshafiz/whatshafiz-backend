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
        Schema::create('university_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name',500)->nullable();            
            $table->text('faculties');
            $table->text('junior_faculties')->nullable();
            $table->string('location',500)->nullable();            
            
        });
    }
    
    /**
    * Reverse the migrations.
    *
    * @return void
    */
    public function down()
    {
        Schema::dropIfExists('university_lists');
    }
};
