<?php

namespace App\Http\Controllers;

use App\Models\University;
use App\Models\UniversityDepartment;
use App\Models\UniversityFaculty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class UniversityController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $cacheKey = 'universities';

        if (Cache::has($cacheKey)) {
            $universities = Cache::get($cacheKey);
        } else {
            $universities = University::orderBy('name')->get(['id', 'name']);
            Cache::put($cacheKey, $universities);
        }

        return response()->json(compact('universities'));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function indexPaginate(Request $request): JsonResponse
    {
        $this->authorize('update', University::class);

        $searchKey = $this->getTabulatorSearchKey($request);

        $universities = University::withCount('faculties', 'departments', 'users')
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where('id', $searchKey)
                    ->orWhere('name', 'LIKE', '%' . $searchKey . '%');
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends($this->filters);

        return response()->json($universities->toArray());
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function indexFacultiesPaginate(Request $request): JsonResponse
    {
        $this->authorize('update', University::class);

        $searchKey = $this->getTabulatorSearchKey($request);

        $faculties = UniversityFaculty::with('university')
            ->withCount('departments', 'users')
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where('id', $searchKey)
                    ->orWhere('name', 'LIKE', '%' . $searchKey . '%');
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends($this->filters);

        return response()->json($faculties->toArray());
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function indexDepartmentsPaginate(Request $request): JsonResponse
    {
        $this->authorize('update', University::class);

        $searchKey = $this->getTabulatorSearchKey($request);

        $departments = UniversityDepartment::with('university', 'faculty')
            ->withCount('users')
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where('id', $searchKey)
                    ->orWhere('name', 'LIKE', '%' . $searchKey . '%');
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends($this->filters);

        return response()->json($departments->toArray());
    }

    /**
     * @param  University  $university
     * @return JsonResponse
     */
    public function show(University $university): JsonResponse
    {
        $university->load('faculties');

        return response()->json($university->toArray());
    }

    /**
     * @param  UniversityFaculty  $faculty
     * @return JsonResponse
     */
    public function showFaculty(UniversityFaculty $faculty): JsonResponse
    {
        $faculty->load('departments');

        return response()->json($faculty->toArray());
    }

    /**
     * @param  UniversityDepartment  $department
     * @return JsonResponse
     */
    public function showDepartment(UniversityDepartment $department): JsonResponse
    {
        return response()->json($department->toArray());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  University  $university
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, University $university): JsonResponse
    {
        $this->authorize('update', University::class);

        $validatedUniversityData = $this->validate(
            $request,
            [
                'name' => 'required|string|unique:universities,name,' . $university->id,
            ]
        );

        $university->update($validatedUniversityData);

        return response()->json(compact('university'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  UniversityFaculty  $faculty
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function updateFaculty(Request $request, UniversityFaculty $faculty): JsonResponse
    {
        $this->authorize('update', University::class);

        $validatedFacultyData = $this->validate(
            $request,
            [
                'university_id' => 'required|integer|min:1|exists:universities,id',
                'name' => 'required|string|unique:university_faculties,name,' . $faculty->id .
                    ',id,university_id,' . $request->university_id,
            ]
        );

        $faculty->update($validatedFacultyData);

        return response()->json(compact('faculty'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  UniversityDepartment  $department
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function updateDepartment(Request $request, UniversityDepartment $department): JsonResponse
    {
        $this->authorize('update', University::class);

        $validatedDepartmentData = $this->validate(
            $request,
            [
                'university_id' => 'required|integer|min:1|exists:universities,id',
                'university_faculty_id' => 'required|integer|min:1|exists:university_faculties,id',
                'name' => 'required|string|unique:university_departments,name,' . $department->id .
                    ',id,university_id,' . $request->university_id .
                    ',university_faculty_id,' . $request->university_faculty_id,
            ]
        );

        $department->update($validatedDepartmentData);

        return response()->json(compact('department'));
    }

    /**
     * @param  University  $university
     * @return JsonResponse
     */
    public function faculties(University $university): JsonResponse
    {
        $cacheKey = "universities:{$university->id}:faculties";

        if (Cache::has($cacheKey)) {
            $faculties = Cache::get($cacheKey);
        } else {
            $faculties = $university->faculties()->get(['id', 'name']);
            Cache::put($cacheKey, $faculties);
        }

        return response()->json(compact('faculties'));
    }

    /**
     * @param  University  $university
     * @param  UniversityFaculty  $faculty
     * @return JsonResponse
     */
    public function departments(University $university, UniversityFaculty $faculty): JsonResponse
    {
        $cacheKey = "universities:{$university->id}:faculties:{$faculty->id}:departments";

        if (Cache::has($cacheKey)) {
            $departments = Cache::get($cacheKey);
        } else {
            $departments = $faculty->departments()->get(['id', 'name']);
            Cache::put($cacheKey, $departments);
        }

        return response()->json(compact('departments'));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|min:5|max:250|unique:universities']);

        $university = University::create(['name' => $request->name]);

        Cache::forget("universities");

        return response()->json(compact('university'), Response::HTTP_CREATED);
    }

    /**
     * @param  University  $university
     * @param  Request  $request
     * @return JsonResponse
     */
    public function storeFaculty(University $university, Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:5|max:250' .
                '|unique:university_faculties,name,NULL,NULL,university_id,' . $university->id,
        ]);

        $faculty = $university->faculties()->create(['name' => $request->name]);

        Cache::forget("universities:{$university->id}:faculties");

        return response()->json(compact('faculty'), Response::HTTP_CREATED);
    }

    /**
     * @param  University  $university
     * @param  UniversityFaculty  $faculty
     * @param  Request  $request
     * @return JsonResponse
     */
    public function storeDepartment(University $university, UniversityFaculty $faculty, Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:5|max:250' .
                '|unique:university_departments,name,NULL,NULL,university_id,' . $university->id,
        ]);

        $department = $faculty->departments()
            ->create(['university_id' => $faculty->university_id, 'name' => $request->name]);

        Cache::forget("universities:{$university->id}:faculties:{$faculty->id}:departments");

        return response()->json(compact('department'), Response::HTTP_CREATED);
    }

    /**
     * @param  University  $university
     * @return JsonResponse
     */
    public function destroy(University $university): JsonResponse
    {
        $this->authorize('delete', University::class);

        if ($university->faculties()->exists()) {
            return response()->json(
                ['message' => 'Üniversite silinemez, çünkü içinde fakülteler mevcut.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($university->users()->exists()) {
            return response()->json(
                ['message' => 'Üniversite silinemez, çünkü seçmiş olan kullanıcılar mevcut.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $university->delete();
        Cache::forget("universities");

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  UniversityFaculty  $faculty
     * @return JsonResponse
     */
    public function destroyFaculty(UniversityFaculty $faculty): JsonResponse
    {
        $this->authorize('delete', University::class);

        if ($faculty->departments()->exists()) {
            return response()->json(
                ['message' => 'Fakülte silinemez, çünkü içinde bölümler mevcut.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($faculty->users()->exists()) {
            return response()->json(
                ['message' => 'Fakülte silinemez, çünkü seçmiş olan kullanıcılar mevcut.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        Cache::forget("universities:{$faculty->university_id}:faculties");
        $faculty->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  UniversityDepartment  $department
     * @return JsonResponse
     */
    public function destroyDepartment(UniversityDepartment $department): JsonResponse
    {
        $this->authorize('delete', University::class);

        if ($department->users()->exists()) {
            return response()->json(
                ['message' => 'Bölüm silinemez, çünkü seçmiş olan kullanıcılar mevcut.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        Cache::forget(
            "universities:{$department->university_id}:faculties:{$department->university_faculty_id}:departments"
        );
        $department->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
