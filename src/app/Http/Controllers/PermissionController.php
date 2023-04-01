<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = Permission::orderBy('id')->get(['id', 'name']);

        return response()->json(compact('permissions'));
    }
}
