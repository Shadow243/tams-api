<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as BasePermission;

/**
 * @mixin IdeHelperPermission
 */
final class Permission extends BasePermission
{
    use HasFactory;
}
