<?php

use App\Models\Permission;
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
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('label')->nullable()->after('id');
        });

        Permission::where('name', 'LIKE', 'quiz.%')->delete();
        Permission::where('name', 'LIKE', '%user-view')->delete();
        Permission::where('name', 'LIKE', '%user-update')->delete();

        $permissions = Permission::get();

        foreach ($permissions as $permission) {
            $permission->update(['label' => Permission::generateLabel($permission->name)]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
