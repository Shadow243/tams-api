<?php

declare(strict_types=1);

namespace App\Services\Translation;

use App\Tams;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

final class TranslationService
{
    /**
     * Get cached translation messages for the given locale.
     *
     * @return array<string, mixed>
     */
    public function getMessages(string $locale): array
    {
        if (! Tams::getAvailableLocaleCodes()->contains($locale)) {
            abort(404, 'Locale not supported.');
        }

        $cacheKey = sprintf('i18n.messages.%s', $locale);

        return Cache::rememberForever($cacheKey, function () use ($locale): array {
            $directory = lang_path($locale);
            $messages = [];

            foreach (File::files($directory) as $file) {
                $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $messages[$filename] = require $file->getPathname();
            }

            return $messages;
        });
    }
}
