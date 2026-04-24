<?php

namespace Modules\ManageDb\Contracts;

use App\Models\Hosting;

interface ManageDbNamingInterface
{
    public function prefixForHosting(Hosting $hosting): string;

    public function fullName(Hosting $hosting, string $raw): string;

    public function assertSafeIdentifier(string $name): void;
}
