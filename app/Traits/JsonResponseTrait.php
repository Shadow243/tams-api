<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait JsonResponseTrait
{
    public function sendData($result, int $status = 200): JsonResponse
    {
        return response()->json($result, $status, []);
    }

    /**
     * @return JsonResponse
     */
    public function sendMessage(string $message, int $status = Response::HTTP_OK)
    {
        return response()->json(compact('message'), $status, [], JSON_NUMERIC_CHECK);
    }

    /**
     * @return JsonResponse
     */
    public function sendErrorResponse(string $message, int $status = Response::HTTP_UNPROCESSABLE_ENTITY)
    {
        return response()->json(['message' => $message], $status, [], JSON_NUMERIC_CHECK);
    }
}
