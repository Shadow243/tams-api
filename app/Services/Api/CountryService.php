<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Country;
use Illuminate\Http\Request;

/**
 * Class CountryService
 * @package App\Services\Api
 */
final class CountryService
{
    /**
     * Store a newly created resource in storage.
     * @param array $data
     * @return Country
     */
    public function create(array $data): Country
    {
        return Country::create($data);
    }

    /**
     * Update the specified resource in storage.
     * @param Country $country
     * @param array $data
     * @return Country
     */
    public function update(Country $country, array $data): Country
    {
        $country->update($data);
        return $country->fresh();
    }

    /**
     * Remove the specified resource from storage.
     * @param Country $country
     */
    public function destroy(Country $country): void
    {
        $country->delete();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCountries(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 10), 100);
        $search = $request->input('search');

        $query = Country::query()->select(['id', 'name', 'code', 'created_at', 'updated_at']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhere('code', 'like', '%' . addcslashes($search, '%_\\') . '%');
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }
}
