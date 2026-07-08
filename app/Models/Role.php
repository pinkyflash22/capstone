<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class Role extends Model
{
    protected $table = 'roles';
    public const ROLE_DEFINITION_CACHE_KEY = 'role:definitions.by_id';

   protected $primaryKey = 'role_id';
    protected $fillable = ['role'];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'role_id');
    }

    public static function definitionMap(): array
    {
        return Cache::rememberForever(self::ROLE_DEFINITION_CACHE_KEY, function () {
            return self::query()
                ->select(['role_id', 'role'])
                ->get()
                ->mapWithKeys(fn (self $role) => [$role->role_id => strtolower((string) $role->role)])
                ->all();
        });
    }

    public static function roleNameForId(?int $roleId): ?string
    {
        if (! $roleId) {
            return null;
        }

        return self::definitionMap()[$roleId] ?? null;
    }

    public static function flushDefinitionCache(): void
    {
        Cache::forget(self::ROLE_DEFINITION_CACHE_KEY);
    }
}
