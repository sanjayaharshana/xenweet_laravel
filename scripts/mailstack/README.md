Local mail stack scripts
========================

This folder contains provisioning helpers used by `App\Services\MailStackProvisioner`.

What these scripts manage
-------------------------
- Dovecot passwd file at: `<state_root>/dovecot/users.passwd`
- Maildir folders at: `<state_root>/vmail/<domain>/<local_part>/Maildir`

Laravel config
--------------
Set these in `.env`:

- `MAIL_STACK_ENABLED=true`
- `MAIL_STACK_STATE_ROOT=/var/lib/xenweet-mailstack` (or any writable path)
- `MAIL_STACK_PROVISION_SCRIPT=/path/to/scripts/mailstack/provision-mailbox.sh`
- `MAIL_STACK_REMOVE_SCRIPT=/path/to/scripts/mailstack/remove-mailbox.sh`

Dovecot/Postfix wiring (server-side)
------------------------------------
Example Dovecot passwd-file setup:

- `passdb { driver = passwd-file args = scheme=BLF-CRYPT username_format=%u /var/lib/xenweet-mailstack/dovecot/users.passwd }`
- `userdb { driver = static args = uid=vmail gid=vmail home=/var/lib/xenweet-mailstack/vmail/%d/%n }`

Example Postfix virtual transport hints:

- `virtual_transport = lmtp:unix:private/dovecot-lmtp`
- `virtual_mailbox_domains = hash:/etc/postfix/virtual_mailbox_domains`
- `virtual_mailbox_maps = hash:/etc/postfix/virtual_mailbox_maps`

Update those maps to include hosted domains and mailbox addresses.
