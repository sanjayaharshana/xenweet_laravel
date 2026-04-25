<?php

namespace App\Support;

use Nwidart\Modules\Facades\Module;

final class ModuleSettings
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $service = self::service();
        if ($service === null) {
            return $default;
        }

        return $service->get($key, $default);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $service = self::service();
        if ($service === null) {
            return $default;
        }

        return $service->bool($key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $service = self::service();
        if ($service === null) {
            return [];
        }

        return $service->all();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function upsertMany(array $values): void
    {
        $service = self::service();
        if ($service === null) {
            return;
        }

        $service->upsertMany($values);
    }

    private static function service(): ?object
    {
        $serviceClass = 'Modules\\ManageSetting\\Services\\ManageSettingService';

        if (! class_exists($serviceClass)) {
            return null;
        }

        if (! Module::isEnabled('ManageSetting')) {
            return null;
        }

        return app($serviceClass);
    }
}
