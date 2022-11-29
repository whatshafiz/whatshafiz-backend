<?php

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
        Schema::create('whatsapp_group_users', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(WhatsappGroup::class)->constrained();
            $table->foreignIdFor(User::class, 'user_id')->constrained('users');
            $table->datetime('joined_at')->useCurrent();
            $table->enum('role_type', ['hafizol', 'hafizkal'])->nullable();
            $table->boolean('is_moderator')->default(false);
            $table->datetime('moderation_started_at')->nullable();
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
        Schema::dropIfExists('whatsapp_group_users');
    }
};
