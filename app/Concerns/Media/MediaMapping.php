<?php

namespace App\Concerns\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final readonly class MediaMapping
{

    public function __construct(
        private string $property,
        private string $directory,
        private string|\Closure $filename,
        public string $disk = 'public'
    ){

    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getFilename(Model $model, UploadedFile $file): string
    {
        $filename = is_string($this->filename) ? $model->getAttribute($this->filename) : ($this->filename)($model, $file);
        return sprintf('%s.%s', $filename, $file->clientExtension());
    }

}