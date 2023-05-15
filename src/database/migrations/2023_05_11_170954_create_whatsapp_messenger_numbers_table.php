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
        Schema::create('whatsapp_messenger_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 100)->nullable();
            $table->string('instance_id', 100)->nullable();
            $table->string('qrcode_url')->nullable();
            $table->string('screenshots_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity_at')->nullable();
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
        Schema::dropIfExists('whatsapp_messenger_numbers');
    }
};
