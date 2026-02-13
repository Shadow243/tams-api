<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Users;

use App\Services\Api\User\UserService;
use App\Http\Requests\Api\UserRequest;
use App\Http\Resources\Api\UserResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

/**
 * @group Configurations
 *
 * @subgroup Users
 */
class UserController extends Controller
{
    public function __construct(private UserService $userService){}

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
}