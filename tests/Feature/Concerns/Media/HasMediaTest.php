<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use App\Concerns\Media\HasMedia;

beforeEach(function () {
   Storage::fake('public');

   Schema::create('media_test', function ($table) {
      $table->id();
      $table->string('name')->nullable();
       $table->string('namewithid')->nullable();
       $table->string('slug')->nullable();
      $table->timestamps();
   });

   Str::createRandomStringsUsing(fn () => 'aaa');
   
   // Define test model helper
   $this->getTestModel = function () {
       return new class extends Model {
           use HasMedia;

           protected $table = 'media_test';
           protected $guarded = [];

           protected static function booted()
           {
               self::registerMediaForProperty(
                   property: 'name',
                   directory: 'documents',
                   filename: 'slug'
               );
               self::registerMediaForProperty(
                   property: 'namewithid',
                   directory: 'documents',
                   filename: fn ($model) => $model->id . '-' . Str::random(16),
               );
           }
       };
   };
});

afterEach(function () {
   Schema::dropIfExists('media_test');
   Str::createRandomStringsNormally();
});

it('should attach media correctly', function () {
    $model = ($this->getTestModel)();
    $model->slug = 'demo';
    $model->save();
    $model->attachMedia(UploadedFile::fake()->create('cv.pdf', 100), 'name');
    expect($model->name)->toBe('demo.pdf');
    Storage::disk('public')->assertExists('documents/demo.pdf');
});