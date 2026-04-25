<?php

namespace Modules\ManageSetting\Support;

use Modules\ManageSetting\Services\ManageSettingService;

final class Settings
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::service()->get($key, $default);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        return self::service()->bool($key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::service()->all();
    }

    private static function service(): ManageSettingService
    {
        return app(ManageSettingService::class);
    }
}
