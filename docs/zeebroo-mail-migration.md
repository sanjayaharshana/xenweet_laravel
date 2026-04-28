# ZeeBroo Mail Migration Notes

This project now uses **ZeeBroo Mail** and stores mailbox artifacts per host under:

- `<host_root_path>/mail/vmail/<domain>/<local_part>/Maildir`
- `<host_root_path>/mail/dovecot/users.passwd`

## Previous layout

Earlier setups could use a global mail stack root:

- `MAIL_STACK_STATE_ROOT` (default `storage/app/mailstack`)

## One-time migration checklist

1. Disable account create/delete actions during migration window.
2. For each hosting account, ensure `<host_root_path>/mail` exists and is writable by the panel process.
3. Copy existing mailbox trees from old global root to each host root:
   - from: `<old_state_root>/vmail/<domain>/<local_part>/Maildir`
   - to: `<host_root_path>/mail/vmail/<domain>/<local_part>/Maildir`
4. Merge/update per-host password file:
   - source rows from `<old_state_root>/dovecot/users.passwd`
   - destination `<host_root_path>/mail/dovecot/users.passwd`
5. Ensure ownership/permissions for your Dovecot/Postfix setup.
6. Verify IMAP login and send flow for at least one mailbox per host.
7. Keep `MAIL_STACK_STATE_ROOT` as fallback only; primary path is host-local.

## Operational note

If a host path is not writable, provisioning falls back to configured `MAIL_STACK_STATE_ROOT` and logs a warning (`mail_stack_host_root_fallback`).

