<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function indexPaginate(Request $request): JsonResponse
    {
        $searchKey = $this->getTabulatorSearchKey($request);

        $roles = Role::where('name', '!=', 'Admin')
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where('id', $searchKey)
                    ->orWhere('name', 'LIKE', '%' . $searchKey . '%');
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends($this->filters);

        return response()->json($roles->toArray());
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $roles = Role::get();

        return response()->json(compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  Request  $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
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
     * @param  Role  $role
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(Role $role): JsonResponse
    {
        if ($role->name === 'Admin') {
            return response()->json(
                ['message' => 'Admin bilgileri değiştirilemez!'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $role->load('permissions');

        return response()->json(compact('role'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Role  $role
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        if ($role->name === 'Admin') {
            return response()->json(
                ['message' => 'Admin bilgileri değiştirilemez!'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $validatedRoleData = $this->validate(
            $request,
            [
                'name' => 'required|string|unique:roles,name,' . $role->id,
                'permissions' => 'nullable|array',
                'permissions.*' => 'required|integer|min:1|exists:permissions,id',
            ]
        );

        $role->update($validatedRoleData);
        $role->syncPermissions($validatedRoleData['permissions']);

        return response()->json(compact('role'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Role  $role
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->name === 'Admin') {
            return response()->json(
                ['message' => 'Admin silinemez!'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

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
