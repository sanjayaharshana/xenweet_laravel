<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local Mail Stack Toggle
    |--------------------------------------------------------------------------
    |
    | When enabled, mailbox CRUD in the Email module will also provision
    | local mail stack artifacts for Dovecot/Postfix (passwd + vmail dirs).
    |
    */
    'enabled' => (bool) env('MAIL_STACK_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Storage root for generated artifacts
    |--------------------------------------------------------------------------
    */
    'state_root' => env('MAIL_STACK_STATE_ROOT', storage_path('app/mailstack')),

    /*
    |--------------------------------------------------------------------------
    | Shell scripts (override per server)
    |--------------------------------------------------------------------------
    */
    'provision_script' => env('MAIL_STACK_PROVISION_SCRIPT', base_path('scripts/mailstack/provision-mailbox.sh')),
    'remove_script' => env('MAIL_STACK_REMOVE_SCRIPT', base_path('scripts/mailstack/remove-mailbox.sh')),

    /*
    |--------------------------------------------------------------------------
    | Command timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('MAIL_STACK_TIMEOUT', 45),
];
