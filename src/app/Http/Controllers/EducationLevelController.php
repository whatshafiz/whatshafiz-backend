<?php

namespace App\Http\Controllers;

use App\Models\EducationLevel;
use Illuminate\Http\JsonResponse;

class EducationLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $educationLevels = EducationLevel::get();

        return response()->json(compact('educationLevels'));
    }
}
