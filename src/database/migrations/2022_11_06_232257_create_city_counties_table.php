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
        Schema::create('city_counties', function (Blueprint $table) {
            $table->id();
            $table->string("city",255);
            $table->string("county",255);
            
        });
    }
    
    /**
    * Reverse the migrations.
    *
    * @return void
    */
    public function down()
    {
        Schema::dropIfExists('city_counties');
    }
};
