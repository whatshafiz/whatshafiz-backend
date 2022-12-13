<?php

use App\Models\City;
use App\Models\Country;
use App\Models\University;
use App\Models\UniversityDepartment;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->nullable()->after('name');
            $table->string('email')->nullable()->after('surname');
            $table->enum('gender', ['male', 'female'])->nullable()->after('email');
            $table->timestamp('verification_code_valid_until')->nullable()->after('phone_number_verified_at');
            $table->string('verification_code')->nullable()->after('phone_number_verified_at');
            $table->foreignIdFor(UniversityDepartment::class)->nullable()->after('id')->constrained();
            $table->foreignIdFor(UniversityFaculty::class)->nullable()->after('id')->constrained();
            $table->foreignIdFor(University::class)->nullable()->after('id')->constrained();
            $table->foreignIdFor(City::class)->nullable()->after('id')->constrained();
            $table->foreignIdFor(Country::class)->nullable()->after('id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['university_id']);
            $table->dropForeign(['university_faculty_id']);
            $table->dropForeign(['university_department_id']);

            $table->dropColumn([
                'surname',
                'email',
                'gender',
                'country_id',
                'city_id',
                'university_id',
                'university_faculty_id',
                'university_department_id',
            ]);
        });
    }
};
