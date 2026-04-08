<?php

namespace Webkul\User\Support;

class StoreManagerRolePermissions
{
    /**
     * ACL permission keys for the Store Manager role: all admin ACL keys except
     * configuration and settings users/roles trees.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return collect(config('acl', []))
            ->filter(fn ($item) => is_array($item) && isset($item['key']))
            ->pluck('key')
            ->filter()
            ->reject(fn (string $key) => self::shouldExclude($key))
            ->unique()
            ->values()
            ->all();
    }

    private static function shouldExclude(string $key): bool
    {
        if ($key === 'configuration') {
            return true;
        }

        return str_starts_with($key, 'settings.users')
            || str_starts_with($key, 'settings.roles');
    }
}
