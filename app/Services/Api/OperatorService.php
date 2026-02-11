<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Service Class For Operator
 * @package App\Services\Api
 */
final class OperatorService
{
    /**
     * Upload Logo For The Operator
     * @param Operator $operator
     * @param UploadedFile $file
     * @param string $field
     * @return Operator
     */
    public function uploadLogo(Operator $operator, UploadedFile $file, string $field = 'logo'): Operator
    {
        $operator->attachMedia($file, $field);
        return $operator;
    }

    /**
     * Store a newly created resource in storage.
     * @param array $data
     * @return Operator
     */
    public function create(array $data): Operator
    {
        // Remove logo from data as it will be handled separately
        unset($data['logo']);
        return Operator::create($data);
    }

    /**
     * Update the specified resource in storage.
     * @param Operator $operator
     * @param array $data
     * @return Operator
     */
    public function update(Operator $operator, array $data): Operator
    {
        // Remove logo from data as it will be handled separately
        unset($data['logo']);
        $operator->update($data);
        return $operator->fresh(['country']);
    }

    /**
     * Remove the specified resource from storage.
     * @param Operator $operator
     */
    public function destroy(Operator $operator): void
    {
        $operator->delete();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getOperators(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 10), 100);
        $search = $request->input('search');
        $countryId = $request->input('country_id');

        $query = Operator::query()
            ->with('country:id,name,code')
            ->select(['id', 'name', 'country_id', 'logo', 'created_at', 'updated_at']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . addcslashes($search, '%_\\\\') . '%');
            });
        }

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        return $query->orderBy('name')->paginate($perPage);
    }
}
