<?php

namespace Modules\ManageDb\Repositories;

use App\Models\Hosting;
use Modules\ManageDb\Contracts\ManageDbNamingInterface;
use RuntimeException;

class ManageDbNaming implements ManageDbNamingInterface
{
    public function prefixForHosting(Hosting $hosting): string
    {
        $baseSource = (string) ($hosting->panel_username ?? '');
        if (trim($baseSource) === '') {
            $baseSource = (string) $hosting->domain;
        }

        $base = strtolower($baseSource);
        $base = preg_replace('/[^a-z0-9]+/', '_', $base) ?? '';
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'host';
        }

        return substr($base, 0, 24);
    }

    public function fullName(Hosting $hosting, string $raw): string
    {
        $raw = strtolower($raw);
        $raw = preg_replace('/[^a-z0-9_]/', '_', $raw) ?? '';
        $raw = trim($raw, '_');
        if ($raw === '') {
            throw new RuntimeException('Name is invalid after normalization.');
        }

        $prefix = $this->prefixForHosting($hosting);
        $name = $prefix.'_'.$raw;

        return substr($name, 0, 64);
    }

    public function assertSafeIdentifier(string $name): void
    {
        if (! preg_match('/^[a-z0-9_]{1,64}$/', $name)) {
            throw new RuntimeException('Unsafe identifier.');
        }
    }
}
