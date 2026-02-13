<?php

declare(strict_types=1);

namespace App\Services\Api\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

/**
 * Service class for managing user-related operations.
 */
final class UserService
{
    /**
     * Create a new user with the provided data.
     *
     * @param array<string, mixed> $data
     * @return User
     */
    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'country_code' => $data['country_code'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'gender' => $data['gender'] ?? null,
        ]);
    }

    /**
     * Update the specified use avatar with the provided data.
     *
     * @param User $user
     * @param UploadedFile $file
     * @param string $field
     * @return User
     */
    public function updateAvatar(User $user, UploadedFile $file, string $field = 'avatar'): User
    {
        $user->attachMedia($file, $field);
        return $user;
    }

    /**
     * Get a paginated list of users based on the provided request parameters.
     * Supports searching by name, email, or phone number.
     * Supports filtering by active status.
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUsers(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('active')) {
            $active = $request->input('active');
            if ($active === '1' || $active === '0') {
                $query->where('active', (bool) $active);
            }
        }

        $perPage = $request->input('paginate', 10);
        return $query->paginate((int) $perPage);
    }

    /**
     * Update the specified user with the provided data.
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @return User
     */
    public function update(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? $user->phone_number,
            'gender' => $data['gender'] ?? $user->gender,
            'country_code' => $data['country_code'] ?? $user->country_code,
        ];

        if (isset($data['password']) && !empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        if (isset($data['active'])) {
            $updateData['active'] = (bool) $data['active'];
        }

        $user->update($updateData);
        return $user->fresh();
    }

    /**
     * Delete the specified user.
     *
     * @param User $user
     * @return bool
     */
    public function destroy(User $user): bool
    {
        return (bool) $user->delete();
    }

    /**
     * Delete multiple users by their IDs.
     *
     * @param array<int> $ids
     * @return int Number of users deleted
     */
    public function bulkDestroy(array $ids): int
    {
        return User::whereIn('id', $ids)->delete();
    }

    /**
     * Export users to PDF format.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportToPDF(Request $request)
    {
        $query = User::query();

        // Apply same filters as getUsers method
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('active')) {
            $active = $request->input('active');
            if ($active === '1' || $active === '0') {
                $query->where('active', (bool) $active);
            }
        }

        // Get all matching users (no pagination for export)
        $users = $query->get();

        // Generate PDF using DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.users', [
            'users' => $users,
            'date' => now()->format('d/m/Y H:i'),
            'total' => $users->count(),
        ]);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'landscape');

        // Return PDF download
        return $pdf->download('users_' . now()->format('Y-m-d') . '.pdf');
    }
}
