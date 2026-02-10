<?php

beforeEach(function () {
   \Illuminate\Support\Facades\Storage::fake('public');

   \Illuminate\Support\Facades\Schema::create('media_test', function ($table) {
      $table->id();
      $table->string('name')->nullable();
       $table->string('namewithid')->nullable();
       $table->string('slug')->nullable();
      $table->timestamps();
   });

   \Illuminate\Support\Str::createRandomStringsUsing(fn () => 'aaa');
});

afterEach(function () {
   \Illuminate\Support\Facades\Schema::dropIfExists('media_test');
   \Illuminate\Support\Str::createRandomStringsNormally();
});

class TestModel extends \Illuminate\Database\Eloquent\Model {

    use \App\Concerns\Media\HasMedia;

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
            filename: fn ($model) => $model->id . '-' . \Illuminate\Support\Str::random(16),
        );
    }

}

it('should attach media correctly', function () {
    $model = new TestModel();
    $model->slug = 'demo';
    $model->save();
    $model->attachMedia(\Illuminate\Http\UploadedFile::fake()->create('cv.pdf', 100), 'name');
    expect($model->name)->toBe('demo.pdf');
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists('documents/demo.pdf');
});