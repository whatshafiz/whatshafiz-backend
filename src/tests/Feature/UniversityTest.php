<?php

namespace Tests\Feature;

use App\Models\University;
use App\Models\UniversityDepartment;
use App\Models\UniversityFaculty;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\BaseFeatureTest;

class UniversityTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/universities';
    }

    /** @test */
    public function it_should_get_university_list()
    {
        Cache::shouldReceive('has')->with('universities')->once()->andReturn(false);
        Cache::shouldReceive('get')->with('universities')->never();
        Cache::shouldReceive('put')->once();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach (University::get() as $university) {
            $response->assertJsonFragment($university->only('id', 'name'));
        }
    }

    /** @test */
    public function it_should_get_university_list_from_cache_when_universities_list_cached_before()
    {
        $dummyUniversities = University::inRandomOrder()->take(rand(3, 5))->get();
        Cache::shouldReceive('has')->with('universities')->once()->andReturn(true);
        Cache::shouldReceive('get')->with('universities')->once()->andReturn($dummyUniversities);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($dummyUniversities as $university) {
            $response->assertJsonFragment($university->only('id', 'name'));
        }
    }

    /** @test */
    public function it_should_get_university_details()
    {
        $university = University::inRandomOrder()->first();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $university->id);

        $response->assertOk()
            ->assertJsonFragment($university->toArray());
    }

    /** @test */
    public function it_should_get_university_faculty_details()
    {
        $faculty = UniversityFaculty::inRandomOrder()->first();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', self::BASE_URI . '/faculties/' . $faculty->id);

        $response->assertOk()
            ->assertJsonFragment($faculty->toArray());
    }

    /** @test */
    public function it_should_get_university_department_details()
    {
        $department = University::inRandomOrder()->first();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', self::BASE_URI . '/departments/' . $department->id);

        $response->assertOk()
            ->assertJsonFragment($department->toArray());
    }

    /** @test */
    public function it_should_paginate_university_list()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('universities.update');

        $perPage = 10;

        $response = $this->actingAs($user)->json('GET', $this->uri . '/paginate', ['size' => $perPage]);

        $response->assertOk()
            ->assertJsonFragment(['per_page' => $perPage]);

        foreach (University::take($perPage)->latest('id')->get() as $university) {
            $response->assertJsonFragment($university->toArray());
        }
    }

    /** @test */
    public function it_should_paginate_faculty_list()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('universities.update');

        $perPage = 10;

        $response = $this->actingAs($user)->json('GET', self::BASE_URI . '/faculties/paginate', ['size' => $perPage]);

        $response->assertOk()
            ->assertJsonFragment(['per_page' => $perPage]);

        foreach (UniversityFaculty::take($perPage)->latest('id')->get() as $city) {
            $response->assertJsonFragment($city->toArray());
        }
    }

    /** @test */
    public function it_should_paginate_department_list()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('universities.update');

        $perPage = 10;

        $response = $this->actingAs($user)->json('GET', self::BASE_URI . '/departments/paginate', ['size' => $perPage]);

        $response->assertOk()
            ->assertJsonFragment(['per_page' => $perPage]);

        foreach (UniversityDepartment::take($perPage)->latest('id')->get() as $city) {
            $response->assertJsonFragment($city->toArray());
        }
    }

    /** @test */
    public function it_should_get_university_faculty_list()
    {
        $university = University::inRandomOrder()->whereHas('faculties')->with('faculties')->first();
        $cacheKey = "universities:{$university->id}:faculties";
        Cache::shouldReceive('has')->with($cacheKey)->once()->andReturn(false);
        Cache::shouldReceive('get')->with($cacheKey)->never();
        Cache::shouldReceive('put')->once();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $university->id . '/faculties');

        $response->assertOk();

        foreach ($university->faculties as $faculty) {
            $response->assertJsonFragment($faculty->only('id', 'name'));
        }
    }

    /** @test */
    public function it_should_get_university_faculty_list_from_cache_when_faculties_list_cached_before()
    {
        $university = University::inRandomOrder()->whereHas('faculties')->with('faculties')->first();
        $cacheKey = "universities:{$university->id}:faculties";
        Cache::shouldReceive('has')->with($cacheKey)->once()->andReturn(true);
        Cache::shouldReceive('get')->with($cacheKey)->once()->andReturn($university->faculties);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $university->id . '/faculties');

        $response->assertOk();

        foreach ($university->faculties as $faculty) {
            $response->assertJsonFragment($faculty->only('id', 'name'));
        }
    }

    /** @test */
    public function it_should_get_university_faculty_department_list()
    {
        $faculty = UniversityFaculty::inRandomOrder()->whereHas('departments')->with('departments')->first();
        $cacheKey = "universities:{$faculty->university_id}:faculties:{$faculty->id}:departments";
        Cache::shouldReceive('has')->with($cacheKey)->once()->andReturn(false);
        Cache::shouldReceive('get')->with($cacheKey)->never();
        Cache::shouldReceive('put')->once();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->json('GET', $this->uri . '/' . $faculty->university_id . '/faculties/' . $faculty->id . '/departments');

        $response->assertOk();

        foreach ($faculty->departments as $department) {
            $response->assertJsonFragment($department->only('id', 'name'));
        }
    }

    /** @test */
    public function it_should_get_university_faculty_department_list_from_cache_when_department_list_cached_before()
    {
        $faculty = UniversityFaculty::inRandomOrder()->whereHas('departments')->with('departments')->first();
        $cacheKey = "universities:{$faculty->university_id}:faculties:{$faculty->id}:departments";
        Cache::shouldReceive('has')->with($cacheKey)->once()->andReturn(true);
        Cache::shouldReceive('get')->with($cacheKey)->once()->andReturn($faculty->departments);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->json('GET', $this->uri . '/' . $faculty->university_id . '/faculties/' . $faculty->id . '/departments');

        $response->assertOk();

        foreach ($faculty->departments as $department) {
            $response->assertJsonFragment($department->only('id', 'name'));
        }
    }

    /** @test */
    public function it_should_create_university_when_has_permission()
    {
        $user = User::factory()->create();
        $universityData = University::factory()->raw();
        Cache::shouldReceive('forget')->with('universities')->once();

        $response = $this->actingAs($user)->json('POST', $this->uri, $universityData);

        $response->assertCreated()
            ->assertJsonFragment($universityData);

        $this->assertDatabaseHas('universities', $universityData);
    }

    /** @test */
    public function it_should_create_university_faculty_when_has_permission()
    {
        $user = User::factory()->create();
        $facultyData = UniversityFaculty::factory()->raw();
        Cache::shouldReceive('forget')->with("universities:{$facultyData['university_id']}:faculties")->once();

        $response = $this->actingAs($user)
            ->json('POST', $this->uri . '/' . $facultyData['university_id'] . '/faculties', $facultyData);

        $response->assertCreated()
            ->assertJsonFragment($facultyData);

        $this->assertDatabaseHas('university_faculties', $facultyData);
    }

    /** @test */
    public function it_should_create_university_faculty_department_when_has_permission()
    {
        $user = User::factory()->create();
        $departmentData = UniversityDepartment::factory()->raw();
        Cache::shouldReceive('forget')
            ->with(
                "universities:{$departmentData['university_id']}:faculties:{$departmentData['university_faculty_id']}:departments"
            )
            ->once();

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/' . $departmentData['university_id'] . '/faculties/' .
                    $departmentData['university_faculty_id'] . '/departments',
                $departmentData
            );

        $response->assertCreated()
            ->assertJsonFragment($departmentData);

        $this->assertDatabaseHas('university_departments', $departmentData);
    }

    /** @test */
    public function it_should_not_delete_university_details_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $university = University::whereDoesntHave('faculties')->inRandomOrder()->first();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $university->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_not_delete_university_details_when_university_has_faculties()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('universities.delete');

        $university = University::whereHas('faculties')->inRandomOrder()->first();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $university->id);

        $response->assertUnprocessable();
    }

    /** @test */
    public function it_should_delete_university_details_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('universities.delete');

        $university = University::whereDoesntHave('faculties')->inRandomOrder()->first();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $university->id);

        $response->assertSuccessful();
    }

    /** @test */
    public function it_should_not_delete_faculty_details_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $faculty = UniversityFaculty::whereDoesntHave('departments')->inRandomOrder()->first();

        $response = $this->actingAs($user)->json('DELETE', self::BASE_URI . '/faculties/' . $faculty->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_not_delete_faculty_details_when_faculty_has_departments()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('universities.delete');

        $faculty = UniversityFaculty::whereHas('departments')->inRandomOrder()->first();

        $response = $this->actingAs($user)->json('DELETE', self::BASE_URI . '/faculties/' . $faculty->id);

        $response->assertUnprocessable();
    }

    /** @test */
    public function it_should_delete_faculty_details_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('universities.delete');

        $faculty = UniversityFaculty::whereDoesntHave('departments')->inRandomOrder()->first();

        $response = $this->actingAs($user)->json('DELETE', self::BASE_URI . '/faculties/' . $faculty->id);

        $response->assertSuccessful();
    }
}
