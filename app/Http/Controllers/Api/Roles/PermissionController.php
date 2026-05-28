<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Roles;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('module_name')
            ->orderBy('group')
            ->orderBy('name')
            ->get();

        $grouped = $permissions
            ->groupBy('module_name')
            ->map(fn ($modulePerms, $moduleName) => [
                'module_name' => $moduleName ?: 'Other',
                'groups'      => $modulePerms
                    ->groupBy('group')
                    ->map(fn ($groupPerms, $groupName) => [
                        'group'       => $groupName ?: 'other',
                        'permissions' => $groupPerms->values(),
                    ])
                    ->values(),
            ])
            ->values();

        return response()->json([
            'data'    => $permissions,
            'grouped' => $grouped,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:permissions,name',
            'group'       => 'required|string|max:100',
            'module_name' => 'required|string|max:100',
        ]);

        $permission = Permission::create($validated);

        app()['cache']->forget('spatie.permission.cache');

        return $this->sendResponse($permission, __('permissions.created'), 201);
    }

    public function show(Permission $permission): JsonResponse
    {
        return $this->sendData(['data' => $permission]);
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:permissions,name,' . $permission->id,
            'group'       => 'required|string|max:100',
            'module_name' => 'required|string|max:100',
        ]);

        $permission->update($validated);

        app()['cache']->forget('spatie.permission.cache');

        return $this->sendResponse($permission, __('permissions.updated'));
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $rolesCount = $permission->roles()->count();
        $usersCount = $permission->users()->count();

        if ($rolesCount > 0 || $usersCount > 0) {
            return $this->sendErrorResponse(
                __('permissions.in_use', ['roles' => $rolesCount, 'users' => $usersCount]),
                422
            );
        }

        $permission->delete();

        app()['cache']->forget('spatie.permission.cache');

        return $this->sendMessage(__('permissions.deleted'));
    }
}
