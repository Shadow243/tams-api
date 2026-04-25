<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Users;

use App\Services\Api\User\UserService;
use App\Http\Requests\Api\UserRequest;
use App\Http\Resources\Api\UserResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * @group Configurations
 *
 * @subgroup Users
 */
class UserController extends Controller
{
    public function __construct(private UserService $userService){}

    /**
     * Get all available roles.
     */
    public function roles()
    {
        $roles = Role::select('id', 'name')->orderBy('name')->get();
        return $this->sendData($roles);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = $this->userService->getUsers($request);
        
        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $model = $this->userService->create($request->validated());

        if ($request->hasFile('avatar')) {
            $model = $this->userService->updateAvatar($model, $request->file('avatar'));
        }

        return $this->sendResponse(new UserResource($model), __('messages.user_created_successfully'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return $this->sendData(new UserResource($user));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $model = $this->userService->update($user, $request->validated());

        if ($request->hasFile('avatar')) {
            $model = $this->userService->updateAvatar($model, $request->file('avatar'));
        }

        return $this->sendResponse(new UserResource($model), __('messages.user_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->userService->destroy($user);

        return $this->sendMessage(__('messages.user_deleted_successfully'));
    }

    /**
     * Remove multiple users from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:users,id',
        ]);

        $count = $this->userService->bulkDestroy($request->input('ids'));

        return $this->sendMessage(__('messages.users_deleted_successfully', ['count' => $count]));
    }

    /**
     * Export users list to PDF.
     */
    public function exportPDF(Request $request)
    {
        return $this->userService->exportToPDF($request);
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verify current password
        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return $this->sendErrorResponse(__('messages.current_password_incorrect'), 422);
        }

        // Update password
        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $user->save();

        return $this->sendMessage(__('messages.password_updated_successfully'));
    }

    /**
     * Update authenticated user's password.
     */
    public function updateOwnPassword(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verify current password
        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return $this->sendErrorResponse(__('messages.current_password_incorrect'), 422);
        }

        // Update password
        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $user->save();

        return $this->sendMessage(__('messages.password_updated_successfully'));
    }

    /**
     * Upload user avatar.
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $user = $this->userService->updateAvatar($user, $request->file('avatar'));
        }

        return $this->sendResponse(new UserResource($user), __('messages.avatar_updated_successfully'));
    }

    /**
     * Get user settings.
     */
    public function getSettings(Request $request)
    {
        $user = $request->user();
        
        return $this->sendData([
            'settings' => $user->settings ?? []
        ]);
    }

    /**
     * Update user settings.
     */
    public function updateSettings(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'settings' => 'required|array',
        ]);

        $user->update([
            'settings' => $request->input('settings')
        ]);

        return $this->sendResponse([
            'settings' => $user->fresh()->settings
        ], __('Settings updated successfully'));
    }

    /**
     * Update authenticated user's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female,other',
        ]);

        $user->update($validated);

        return $this->sendResponse(new UserResource($user->fresh()), __('messages.profile_updated_successfully'));
    }
}