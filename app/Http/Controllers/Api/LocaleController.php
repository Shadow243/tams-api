<?php

namespace App\Http\Controllers\Api;

use App\Tams;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Translation\TranslationService;

/**
 * @unauthenticated
 */
final class LocaleController extends Controller
{
    public function __invoke()
    {
        return Tams::getAvailableLocales();
    }

    /**
     * @urlParam locale The language to get translations for. Enum: en, fr Example: fr
     */
    public function getTranslations(string $locale, TranslationService $translationService): JsonResponse
    {
        return response()->json($translationService->getMessages($locale));
    }
}
