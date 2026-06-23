<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'label', 'description'])]
class Role extends Model
{
    public const SUPER_ADMIN = 'super_admin';
    public const ADMIN = 'admin';
    public const USER = 'user';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasPermission(string $name): bool
    {
        return $this->permissions->contains('name', $name);
    }
}
