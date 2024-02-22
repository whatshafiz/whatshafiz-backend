<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CourseWhatsappGroupsOrganizer;
use App\Models\City;
use App\Models\Course;
use App\Models\TeacherStudent;
use App\Models\University;
use App\Models\User;
use App\Models\UserCourse;
use App\Models\WhatsappGroup;
use App\Models\WhatsappGroupUser;
use Tests\BaseFeatureTest;

class CourseWhatsappGroupsOrganizerTest extends BaseFeatureTest
{
    /** @test */
    public function it_should_assign_users_to_whatsapp_groups_by_grouping_users_their_closest_attributes()
    {
        $course = Course::factory()->create(['type' => $this->faker->randomElement(['whatsenglish', 'whatsarapp'])]);
        $maleWhatsappGroups = WhatsappGroup::factory()
            ->count(2, 5)
            ->create(['type' => $course->type, 'gender' => 'male', 'course_id' => $course->id]);
        $femaleWhatsappGroups = WhatsappGroup::factory()
            ->count(2, 5)
            ->create(['type' => $course->type, 'gender' => 'female', 'course_id' => $course->id]);
        $whatsappGroupCount = $maleWhatsappGroups->count() + $femaleWhatsappGroups->count();
        $userCountPerWhatsappGroup = 10;
        $city = City::inRandomOrder()->first();
        $maleSimilarUsers = User::factory()
            ->count(rand(2, $userCountPerWhatsappGroup))
            ->create([
                'gender' => 'male',
                'country_id' => $city->country_id,
                'city_id' => $city->id,
                'education_level' => $this->faker->randomElement(['İlkokul', 'Lise', 'Lisans', 'Ön Lisans']) . ' Mezunu',
                'university_id' => University::inRandomOrder()->value('id'),
            ]);
        $femaleSimilarUsers = User::factory()
            ->count(rand(2, $userCountPerWhatsappGroup))
            ->create([
                'gender' => 'female',
                'country_id' => $city->country_id,
                'city_id' => $city->id,
                'education_level' => $this->faker->randomElement(['İlkokul', 'Lise', 'Lisans', 'Ön Lisans']) . ' Mezunu',
                'university_id' => University::inRandomOrder()->value('id'),
            ]);
        $maleSimilarUsersSecondGroup = User::factory()
            ->count(rand(2, $userCountPerWhatsappGroup))
            ->create([
                'gender' => 'male',
                'country_id' => $city->country_id,
                'city_id' => $city->id,
                'education_level' => $this->faker->randomElement(['İlkokul', 'Lise', 'Lisans', 'Ön Lisans']) . ' Mezunu',
                'university_id' => University::inRandomOrder()->value('id'),
            ]);
        $femaleSimilarUsersSecondGroup = User::factory()
            ->count(rand(2, $userCountPerWhatsappGroup))
            ->create([
                'gender' => 'female',
                'country_id' => $city->country_id,
                'city_id' => $city->id,
                'education_level' => $this->faker->randomElement(['İlkokul', 'Lise', 'Lisans', 'Ön Lisans']) . ' Mezunu',
                'university_id' => University::inRandomOrder()->value('id'),
            ]);
        $otherUsers = User::factory()->completed()->count($whatsappGroupCount * $userCountPerWhatsappGroup)->create();
        $course->users()->attach($maleSimilarUsers, ['type' => $course->type]);
        $course->users()->attach($maleSimilarUsersSecondGroup, ['type' => $course->type]);
        $course->users()->attach($femaleSimilarUsers, ['type' => $course->type]);
        $course->users()->attach($femaleSimilarUsersSecondGroup, ['type' => $course->type]);
        $course->users()->attach($otherUsers, ['type' => $course->type]);

        $instance = resolve(CourseWhatsappGroupsOrganizer::class, ['course' => $course]);
        app()->call([$instance, 'handle']);

        foreach ($maleSimilarUsers as $maleSimilarUser) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['course_id' => $course->id, 'user_id' => $maleSimilarUser->id]
            );
        }

        foreach ($femaleSimilarUsers as $femaleSimilarUser) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['course_id' => $course->id, 'user_id' => $femaleSimilarUser->id]
            );
        }

        foreach ($maleSimilarUsersSecondGroup as $maleSimilarUser) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['course_id' => $course->id, 'user_id' => $maleSimilarUser->id]
            );
        }

        foreach ($femaleSimilarUsersSecondGroup as $femaleSimilarUser) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['course_id' => $course->id, 'user_id' => $femaleSimilarUser->id]
            );
        }

        foreach ($otherUsers as $otherUser) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['course_id' => $course->id, 'user_id' => $otherUser->id]
            );
        }

        $this->assertEquals(
            1,
            WhatsappGroupUser::whereIn('user_id', $maleSimilarUsers->pluck('id')->toArray())
                ->where('course_id', $course->id)
                ->get()
                ->pluck('whatsapp_group_id')
                ->unique()
                ->count()
        );

        $this->assertEquals(
            1,
            WhatsappGroupUser::whereIn('user_id', $femaleSimilarUsers->pluck('id')->toArray())
                ->where('course_id', $course->id)
                ->get()
                ->pluck('whatsapp_group_id')
                ->unique()
                ->count()
        );

        $this->assertEquals(
            1,
            WhatsappGroupUser::whereIn('user_id', $maleSimilarUsersSecondGroup->pluck('id')->toArray())
                ->where('course_id', $course->id)
                ->get()
                ->pluck('whatsapp_group_id')
                ->unique()
                ->count()
        );

        $this->assertEquals(
            1,
            WhatsappGroupUser::whereIn('user_id', $femaleSimilarUsersSecondGroup->pluck('id')->toArray())
                ->where('course_id', $course->id)
                ->get()
                ->pluck('whatsapp_group_id')
                ->unique()
                ->count()
        );
    }

    /** @test */
    public function it_should_assign_users_to_whatsapp_groups_by_grouping_users_their_similarity_attributes_for_level_2_and_3()
    {
        $course = Course::factory()->create(['type' => $this->faker->randomElement(['whatsenglish', 'whatsarapp'])]);
        $maleWhatsappGroups = WhatsappGroup::factory()
            ->count(2, 5)
            ->create(['type' => $course->type, 'gender' => 'male', 'course_id' => $course->id]);
        $femaleWhatsappGroups = WhatsappGroup::factory()
            ->count(2, 5)
            ->create(['type' => $course->type, 'gender' => 'female', 'course_id' => $course->id]);
        $whatsappGroupCount = $maleWhatsappGroups->count() + $femaleWhatsappGroups->count();
        $userCountPerWhatsappGroup = 10;
        $city = City::inRandomOrder()->first();
        $city2 = City::inRandomOrder()->where('id', '!=', $city->id)->first();
        $maleSimilarUsers = User::factory()
            ->count(rand(2, $userCountPerWhatsappGroup))
            ->completed()
            ->create([
                'gender' => 'male',
                'country_id' => $city->country_id,
                'city_id' => $city->id,
                'education_level' => $this->faker->randomElement(['İlkokul', 'Lise', 'Lisans', 'Ön Lisans']) . ' Mezunu',
            ]);
        $femaleSimilarUsers = User::factory()
            ->count(rand(2, $userCountPerWhatsappGroup))
            ->completed()
            ->create([
                'gender' => 'female',
                'country_id' => $city2->country_id,
                'city_id' => $city2->id,
                'education_level' => $this->faker->randomElement(['İlkokul', 'Lise', 'Lisans', 'Ön Lisans']) . ' Mezunu',
                'university_id' => University::inRandomOrder()->value('id'),
            ]);
        $otherUsers = User::factory()->completed()->count($whatsappGroupCount * $userCountPerWhatsappGroup)->create();
        $course->users()->attach($maleSimilarUsers, ['type' => $course->type]);
        $course->users()->attach($femaleSimilarUsers, ['type' => $course->type]);
        $course->users()->attach($otherUsers, ['type' => $course->type]);

        $instance = resolve(CourseWhatsappGroupsOrganizer::class, ['course' => $course, 'level' => 2]);
        app()->call([$instance, 'handle']);

        foreach ($maleSimilarUsers as $maleSimilarUser) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['course_id' => $course->id, 'user_id' => $maleSimilarUser->id]
            );
        }

        foreach ($femaleSimilarUsers as $femaleSimilarUser) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['course_id' => $course->id, 'user_id' => $femaleSimilarUser->id]
            );
        }

        foreach ($otherUsers as $otherUser) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['course_id' => $course->id, 'user_id' => $otherUser->id]
            );
        }

        $this->assertEquals(
            1,
            WhatsappGroupUser::whereIn('user_id', $maleSimilarUsers->pluck('id')->toArray())
                ->where('course_id', $course->id)
                ->get()
                ->pluck('whatsapp_group_id')
                ->unique()
                ->count()
        );

        $this->assertEquals(
            1,
            WhatsappGroupUser::whereIn('user_id', $femaleSimilarUsers->pluck('id')->toArray())
                ->where('course_id', $course->id)
                ->get()
                ->pluck('whatsapp_group_id')
                ->unique()
                ->count()
        );
    }


    /** @test */
    public function it_should_assign_users_to_whatsapp_groups_by_grouping_users_their_similarity_attributes_for_level_4_and_5()
    {
        $course = Course::factory()->create(['type' => $this->faker->randomElement(['whatsenglish', 'whatsarapp'])]);
        $maleWhatsappGroups = WhatsappGroup::factory()
            ->count(2, 5)
            ->create(['type' => $course->type, 'gender' => 'male', 'course_id' => $course->id]);
        $femaleWhatsappGroups = WhatsappGroup::factory()
            ->count(2, 5)
            ->create(['type' => $course->type, 'gender' => 'female', 'course_id' => $course->id]);
        $whatsappGroupCount = $maleWhatsappGroups->count() + $femaleWhatsappGroups->count();
        $userCountPerWhatsappGroup = 10;
        $city = City::inRandomOrder()->first();
        $city2 = City::inRandomOrder()->where('id', '!=', $city->id)->first();
        $maleSimilarUsers = User::factory()
            ->count(rand(2, $userCountPerWhatsappGroup))
            ->completed()
            ->create([
                'gender' => 'male',
                'country_id' => $city->country_id,
                'city_id' => $city->id,
                'education_level' => 'İlkokul Mezunu',
            ]);
        $femaleSimilarUsers = User::factory()
            ->count(rand(2, $userCountPerWhatsappGroup))
            ->completed()
            ->create([
                'gender' => 'female',
                'country_id' => $city2->country_id,
                'city_id' => $city2->id,
                'education_level' => $this->faker->randomElement(['Lise', 'Lisans', 'Ön Lisans']) . ' Mezunu',
            ]);
        $otherUsers = User::factory()->completed()->count($whatsappGroupCount * $userCountPerWhatsappGroup)->create();
        $course->users()->attach($maleSimilarUsers, ['type' => $course->type]);
        $course->users()->attach($femaleSimilarUsers, ['type' => $course->type]);
        $course->users()->attach($otherUsers, ['type' => $course->type]);

        $instance = resolve(CourseWhatsappGroupsOrganizer::class, ['course' => $course, 'level' => 4]);
        app()->call([$instance, 'handle']);

        foreach ($maleSimilarUsers as $maleSimilarUser) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['course_id' => $course->id, 'user_id' => $maleSimilarUser->id]
            );
        }

        foreach ($femaleSimilarUsers as $femaleSimilarUser) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['course_id' => $course->id, 'user_id' => $femaleSimilarUser->id]
            );
        }

        foreach ($otherUsers as $otherUser) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['course_id' => $course->id, 'user_id' => $otherUser->id]
            );
        }

        $this->assertEquals(
            1,
            WhatsappGroupUser::whereIn('user_id', $maleSimilarUsers->pluck('id')->toArray())
                ->where('course_id', $course->id)
                ->get()
                ->pluck('whatsapp_group_id')
                ->unique()
                ->count()
        );

        $this->assertEquals(
            1,
            WhatsappGroupUser::whereIn('user_id', $femaleSimilarUsers->pluck('id')->toArray())
                ->where('course_id', $course->id)
                ->get()
                ->pluck('whatsapp_group_id')
                ->unique()
                ->count()
        );
    }
}
