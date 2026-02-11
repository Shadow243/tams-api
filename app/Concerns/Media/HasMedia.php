<?php

namespace App\Concerns\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HasMedia
{

    /** @var MediaMapping[] */
    private static array $mediaMappings = [];

    protected static function bootHasMedia(): void
    {
        self::deleted(function (Model $model) {
            foreach (self::$mediaMappings as $property => $mapping) {
                $model->detachMedia($property);
            }
        });
    }

    protected static function registerMediaForProperty(
        string $property,
        string $directory,
        string|\Closure $filename,
        string $disk = 'public'
    ) {
        $mapping = new MediaMapping($property, $directory, $filename, $disk);
        self::$mediaMappings[$property] = $mapping;
    }

    public function attachMedia(UploadedFile $file, string $property): bool
    {
        $mapping = self::getMediaMappingFor($property);
        if (!$this->exists) {
            self::created(function (Model $model) use ($file, $property) {
                $model->attachMedia($file, $property);
            });
            return true;
        }
        $filename = $mapping->getFilename($this, $file);
        $directory = $mapping->getDirectory();
        $stored = $file->storeAs(
            $directory,
            $filename,
            $mapping->disk
        );
        if ($stored) {
            $this->detachMedia($property);
            $this->setAttribute($property, $filename);
            $this->save();
        }
        return boolval($stored);
    }

    public function detachMedia(string $property): bool
    {
        $mapping = self::getMediaMappingFor($property);
        $filename = $this->getAttribute($property);
        if (!$filename) {
            return true;
        }
        return Storage::disk($mapping->disk)->delete(sprintf('%s/%s', $mapping->getDirectory(), $filename));
    }

    public function mediaUrl(string $property): string
    {
        $mapping = self::getMediaMappingFor($property);
        return Storage::disk($mapping->disk)->url(sprintf('%s/%s', $mapping->getDirectory(), $this->getAttribute($property)));
    }

    private static function getMediaMappingFor(string $property): MediaMapping{
        $mapping = self::$mediaMappings[$property] ?? null;
        assert($mapping instanceof MediaMapping, 'There is no media declaration for '. $property);
        return $mapping;
    }
}