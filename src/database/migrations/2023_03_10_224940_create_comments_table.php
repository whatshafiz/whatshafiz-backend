<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['whatshafiz', 'whatsenglish', 'whatsarapp']);
            $table->string('title');
            $table->text('comment');
            $table->foreignIdFor(User::class, 'commented_by_id')->constrained('users');
            $table->boolean('is_approved')->default(false);
            $table->foreignIdFor(User::class, 'approved_by_id')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
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
        Schema::dropIfExists('comments');
    }
};
