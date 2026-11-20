<?php

namespace App\Support;

class UserGateSession
{
    public const KEY_ACCESS = 'usergate.access_token';
    public const KEY_REFRESH = 'usergate.refresh_token';
    public const KEY_EXPIRES = 'usergate.expires_at';
    public const KEY_SYNC_AFTER = 'usergate.sync_after';
    public const KEY_STATELESS = 'usergate.stateless';

    public static function store(array $tokens): void
    {
        if (! empty($tokens['access_token'])) {
            session()->put(self::KEY_ACCESS, encrypt((string) $tokens['access_token']));
        }

        if (! empty($tokens['refresh_token'])) {
            session()->put(self::KEY_REFRESH, encrypt((string) $tokens['refresh_token']));
        }

        if (isset($tokens['expires_in'])) {
            session()->put(self::KEY_EXPIRES, now()->addSeconds((int) $tokens['expires_in'])->timestamp);
        }

        session()->put(self::KEY_SYNC_AFTER, now()->timestamp);
        session()->forget(self::KEY_STATELESS);
    }

    public static function accessToken(): ?string
    {
        $value = session()->get(self::KEY_ACCESS);

        if (! is_string($value)) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function refreshToken(): ?string
    {
        $value = session()->get(self::KEY_REFRESH);

        if (! is_string($value)) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function forget(): void
    {
        session()->forget([
            self::KEY_ACCESS,
            self::KEY_REFRESH,
            self::KEY_EXPIRES,
            self::KEY_SYNC_AFTER,
            self::KEY_STATELESS,
        ]);
    }

    public static function markStateless(): void
    {
        session()->put(self::KEY_STATELESS, true);
    }

    public static function isStateless(): bool
    {
        return (bool) session()->get(self::KEY_STATELESS, false);
    }
}
