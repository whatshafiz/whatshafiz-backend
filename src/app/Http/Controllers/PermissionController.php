<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
     * Show the form for creating a new resource.
     *
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', Permission::class);

        $data = $this->validate(
            $request,
            [
                'name' => 'required|string|unique:permissions,name',
                'guard_name' => 'required|string',
            ]
        );

        $permission = Permission::create($data);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Display the specified resource.
     *
     * @param Permission $permission
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(Permission $permission)
    {
        $this->authorize('view', $permission);

        return response()->json(compact('permission'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param Permission $permission
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, Permission $permission)
    {
        $this->authorize('update', $permission);

        $data = $this->validate(
            $request,
            [
                'name' => 'required|string|unique:permissions,name,' . $permission->id,
                'guard_name' => 'required|string',
            ]
        );

        $permission->update($data);

        return response()->json(compact('permission'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Permission $permission
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Permission $permission)
    {
        $this->authorize('delete', $permission);

        $permission->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
