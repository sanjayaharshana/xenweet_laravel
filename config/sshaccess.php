<?php

return [
    /*
    | Preferred root helper (installed via scripts/install-xenweet-ssh-sudo.sh)
    | Usage: sudo -n <bin> <username> <password> <host_root> <web_root> [public_key_b64]
    */
    'create_account_system_bin' => env('SSHACCESS_CREATE_ACCOUNT_SYSTEM_BIN', '/usr/local/sbin/xenweet-ssh-create-jailed'),

    /*
    | Fallback local script path (used when system bin is unavailable).
    */
    'create_account_script' => env('SSHACCESS_CREATE_ACCOUNT_SCRIPT', base_path('scripts/hosting-ssh-create-jailed.sh')),

    'create_account_timeout' => (int) env('SSHACCESS_CREATE_ACCOUNT_TIMEOUT', 60),
];
