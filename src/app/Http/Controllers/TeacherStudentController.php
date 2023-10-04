<?php

namespace App\Http\Controllers;

use App\Models\TeacherStudent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TeacherStudentController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function myTeachers(): JsonResponse
    {
        $teachers = Auth::user()->teachers()->with('course')->get();

        return response()->json(compact('teachers'));
    }

    /**
     * @return JsonResponse
     */
    public function myStudents(): JsonResponse
    {
        $students = Auth::user()->students()->with('course')->get();

        return response()->json(compact('students'));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function updateStudentStatus(Request $request, TeacherStudent $teacherStudent): JsonResponse
    {
        $validatedStatusData = $this->validate(
            $request,
            [
                'proficiency_exam_passed' => 'nullable|boolean',
                'proficiency_exam_failed_description' => 'required_if:proficiency_exam_passed,false|nullable|string|max:255',
            ],
            [
                'proficiency_exam_failed_description.required_if' => 'Red nedenini belirtmek zorunludur.',
            ]
        );

        $teacherStudent->update($validatedStatusData);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
