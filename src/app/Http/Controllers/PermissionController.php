<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function index(Request $request)
    {
        $this->authorize('viewAny', Permission::class);

        $filters = $this->validate(
            $request,
            [
                'name' => 'nullable|string',
                'guard_name' => 'nullable|string',
            ]
        );

        $permissions = Permission::when(isset($filters['name']), function ($query) use ($filters) {
            return $query->where('name', 'like', '%' . $filters['name'] . '%');
        })
            ->when(isset($filters['guard_name']), function ($query) use ($filters) {
                return $query->where('guard_name', $filters['guard_name']);
            })
            ->latest('id')
            ->paginate()
            ->appends($filters);

        return response()->json(compact('permissions'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function myPermissions(Request $request)
    {
        $permissions = $request->user()->getAllPermissions();

        return response()->json(compact('permissions'));
    }
}
