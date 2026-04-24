<?php

namespace App\Support;

final class ReservedHandles
{
    /** @var list<string> */
    private const LIST = [
        'admin', 'api', 'app', 'auth', 'login', 'logout', 'register',
        'settings', 'u', 'me', 'dashboard', 'public', 'hq',
        'www', 'mail', 'help', 'support', 'about', 'terms', 'privacy',
        'billing', 'home', 'tenant', 'company', 'companies', 'user', 'users',
    ];

    public static function isReserved(string $handle): bool
    {
        return in_array(strtolower(trim($handle)), self::LIST, true);
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::LIST;
    }
}
