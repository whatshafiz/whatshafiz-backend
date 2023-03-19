<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;

class RoleController extends Controller
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
        $this->authorize('viewAny', Role::class);

        $filters = $this->validate(
            $request,
            [
                'name' => 'nullable|string',
                'guard_name' => 'nullable|string',
            ]
        );

        $roles = Role::when(isset($filters['name']), function ($query) use ($filters) {
            return $query->where('name', 'like', '%' . $filters['name'] . '%');
        })
            ->when(isset($filters['guard_name']), function ($query) use ($filters) {
                return $query->where('guard_name', $filters['guard_name']);
            })
            ->latest('id')
            ->paginate()
            ->appends($filters);

        return response()->json(compact('roles'));
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
        $this->authorize('create', Role::class);

        $data = $this->validate(
            $request,
            [
                'name' => 'required|string|unique:roles,guard_name',
                'guard_name' => 'required|string'
            ]
        );

        $role = Role::create($data);

        return response()->json(compact('role'));
    }

    /**
     * Display the specified resource.
     *
     * @param Role $role
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(Role $role)
    {
        $this->authorize('view', $role);

        return response()->json(compact('role'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Role $role
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        $data = $this->validate(
            $request,
            [
                'name' => 'required|string|unique:roles,guard_name,' . $role->id,
                'guard_name' => 'required|string'
            ]
        );

        $role->update($data);

        return response()->json(compact('role'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Role $role
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        $role->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @param Role $role
     * @return JsonResponse
     * @throws AuthorizationException|ValidationException
     */
    public function assignPermissions(Request $request, Role $role)
    {
        $this->authorize('assign', $role);

        $data = $this->validate(
            $request,
            [
                'permissions' => 'required|array',
            ]
        );

        $role->syncPermissions($data['permissions']);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function viewUserRoles(Request $request, User $user)
    {
        $this->authorize('user-view', Role::class);

        $roles = $user->roles()->paginate();

        return response()->json(compact('roles'));
    }

    /**
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function updateUserRoles(Request $request, User $user)
    {
        $this->authorize('user-update', Role::class);

        $data = $this->validate(
            $request,
            [
                'roles' => 'required|array',
            ]
        );

        $user->syncRoles($data['roles']);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
