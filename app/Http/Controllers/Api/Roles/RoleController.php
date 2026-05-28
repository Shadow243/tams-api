<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Roles;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return $this->sendData(['data' => $roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100|unique:roles,name',
            'description'      => 'nullable|string|max:255',
            'permission_ids'   => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['permission_ids'])) {
            $permissions = Permission::whereIn('id', $validated['permission_ids'])->get();
            $role->syncPermissions($permissions);
        }

        app()['cache']->forget('spatie.permission.cache');

        return $this->sendResponse($role->load('permissions'), __('roles.created'), 201);
    }

    public function show(Role $role): JsonResponse
    {
        return $this->sendData(['data' => $role->load('permissions')]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:255',
        ]);

        $role->update($validated);

        app()['cache']->forget('spatie.permission.cache');

        return $this->sendResponse($role->load('permissions'), __('roles.updated'));
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->users()->count() > 0) {
            return $this->sendErrorResponse(__('roles.has_users'), 422);
        }

        $role->delete();

        app()['cache']->forget('spatie.permission.cache');

        return $this->sendMessage(__('roles.deleted'));
    }

    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permission_ids'   => 'required|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        $permissions = Permission::whereIn('id', $validated['permission_ids'])->get();
        $role->syncPermissions($permissions);

        app()['cache']->forget('spatie.permission.cache');

        return $this->sendResponse($role->load('permissions'), __('roles.permissions_updated'));
    }
}
