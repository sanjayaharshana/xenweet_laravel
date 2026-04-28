<?php

use Illuminate\Support\Facades\Route;

it('registers zeebroo mail routes', function (): void {
    expect(Route::has('hosts.zeebroo-mail.index'))->toBeTrue()
        ->and(Route::has('hosts.zeebroo-mail.show'))->toBeTrue()
        ->and(Route::has('hosts.zeebroo-mail.send'))->toBeTrue();
});

