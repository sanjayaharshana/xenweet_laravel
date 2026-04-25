<?php

namespace Modules\ManageSetting\Services;

use Illuminate\Support\Facades\Schema;
use Modules\ManageSetting\Models\Setting;

class ManageSettingService
{
    public function tableReady(): bool
    {
        return Schema::hasTable('settings');
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (! $this->tableReady()) {
            return [];
        }

        return Setting::query()
            ->pluck('value', 'key')
            ->toArray();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($key === '' || ! $this->tableReady()) {
            return $default;
        }

        $value = Setting::query()
            ->where('key', $key)
            ->value('value');

        return $value ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default ? '1' : '0');

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function upsertMany(array $values): void
    {
        if (! $this->tableReady()) {
            return;
        }

        foreach ($values as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $this->toStorageValue($value)]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function seedDefaults(array $values): void
    {
        if (! $this->tableReady()) {
            return;
        }

        foreach ($values as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $this->toStorageValue($value)]
            );
        }
    }

    private function toStorageValue(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $value === null ? null : (string) $value;
    }
}
