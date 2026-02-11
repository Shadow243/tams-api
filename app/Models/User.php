<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Foundation\Auth\User as Authenticatable;
// use Laravel\Passport\Contracts\OAuthenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Casts\TimezoneAwareDateTime;
// use Laravel\Passport\HasApiTokens;
use Laravel\Sanctum\HasApiTokens;
use App\Observers\UserObserver;
use Illuminate\Support\Str;
use App\Traits\Activable;
use App\Traits\HasUuid;
use App\Concerns\Media\HasMedia;
use App\Concerns\Media\MediaMapping;

#[ObservedBy(UserObserver::class)]
final class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Activable, HasApiTokens, HasFactory, HasRoles, HasMedia, HasUuid, KeepsDeletedModels, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'gender',
        'name',
        'email',
        'password',
        'phone_number',
        'country_code',
        'active',
        'timezone'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static array $mediaMapping = [];

    public static function registerMediaForProperty(
    string $property,
    string $directory,
    string|\Closure $filename,
    string $disk = 'public',
): void {
    static::$mediaMapping[$property] = new MediaMapping(
        directory: $directory,
        filename: $filename,
        disk: $disk,
    );
}

    public static function generateUsername(self $user)
    {
        $username = self::generateID($user->name);

        // Ensure ID does not exist
        // Generate new one if ID already exists
        while (self::whereUsername($username)->count() > 0) {
            $username = self::generateID($user->name, mt_rand(1, 9));
        }

        return Str::lower($username);
    }


    public static function findByEmailOrPhone(string $value)
    {
        return self::where('email', $value)->orWhere('phone_number', get_parsed_phone_number($value))->first();
    }

    /**
     * Get the phone number for SMS notifications.
     */
    public function routeNotificationForSms(): string
    {
        return $this->full_number;
    }

    /**
     * Send the email verification notification.
     *
     * This method is intentionally left empty to prevent sending
     * email verification notifications from this model.
     */
    public function sendEmailVerificationNotification(): void
    {
        // keep empty to prevent sending email verification notification from here
    }

    
    public function preferredLocale()
    {
        return $this->locale ?? 'fr';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'created_at' => TimezoneAwareDateTime::class,
            'updated_at' => TimezoneAwareDateTime::class,
        ];
    }

    private static function generateID(string $name, string|int|null $suffix = ''): string
    {
        return Str::slug($name . $suffix, '');
    }

    protected function fullNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => ! is_null($this->phone_number) ? '+' . $this->country_code . $this->phone_number : null
        );
    }
}
