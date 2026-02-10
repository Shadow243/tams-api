<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as BaseRole;

/**
 * @mixin IdeHelperRole
 */
final class Role extends BaseRole
{
    use HasFactory;
}
