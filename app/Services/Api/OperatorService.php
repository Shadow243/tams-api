<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Operator;
use Illuminate\Http\UploadedFile;

final class OperatorService
{
    public function uploadLogo(Operator $operator, UploadedFile $file, string $field = 'logo')
    {
        $operator->attachMedia($file, $field);
        return $operator;
    }

    public function create(array $data): Operator
    {
        return Operator::create([
            'name' => $data['name']
        ]);
    }

    public function getOperators(Request $request)
    {
        $query = Operator::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $query->paginate(10);
    }

}
