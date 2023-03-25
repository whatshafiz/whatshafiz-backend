<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function index()
    {
        $roles = Role::latest('id')->paginate();

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
        $validatedRoleData = $this->validate(
            $request,
            [
                'name' => 'required|string|unique:roles,name',
                'permissions' => 'nullable|array',
                'permissions.*' => 'required|integer|min:1|exists:permissions,id',
            ]
        );

        $validatedRoleData['guard_name'] = 'web';
        $role = Role::create($validatedRoleData);
        $role->syncPermissions($validatedRoleData['permissions'] ?? []);

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
        $role->load('permissions');

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
        $validatedRoleData = $this->validate(
            $request,
            [
                'name' => 'required|string|unique:roles,name,' . $role->id,
                'permissions' => 'required|array',
            ]
        );

        $role->update($validatedRoleData);
        $role->syncPermissions($validatedRoleData['permissions']);

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
        if ($role->users()->exists()) {
            return response()->json(
                ['message' => 'Rol silinemez, çünkü atanmış kullanıcılar mevcut.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $role->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
