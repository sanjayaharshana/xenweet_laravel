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

Permissions (required on the server)
-------------------------------------
PHP-FPM runs as `www-data`. Dovecot reads `users.passwd` and maildirs as `dovecot` / `vmail`.
Create the state root once and allow `www-data` to create subdirs under `vmail/`:

```bash
groupadd -g 5000 vmail 2>/dev/null || true
id -u vmail >/dev/null 2>&1 || useradd -r -u 5000 -g vmail -d /var/lib/vmail -s /usr/sbin/nologin vmail

mkdir -p /var/lib/xenweet-mailstack/{dovecot,vmail}
touch /var/lib/xenweet-mailstack/dovecot/users.passwd

chown -R vmail:vmail /var/lib/xenweet-mailstack
chmod 2770 /var/lib/xenweet-mailstack /var/lib/xenweet-mailstack/vmail /var/lib/xenweet-mailstack/dovecot
chmod 660 /var/lib/xenweet-mailstack/dovecot/users.passwd

usermod -aG vmail www-data
usermod -aG vmail dovecot

systemctl restart php8.4-fpm
systemctl restart dovecot
```

New maildirs inherit group `vmail` (setgid). Ensure `MAIL_STACK_STATE_ROOT` matches the Dovecot passwd path.

Troubleshooting: `Permission denied` under `.../vmail/<domain>/<user>`
--------------------------------------------------------------------
If the **domain** directory already exists (e.g. first message delivered by Postfix before the panel), it is often `0700` and `vmail:vmail`. The PHP user (`www-data`) then cannot `mkdir` the mailbox folder.

As root, normalize the vmail tree once (safe with `www-data` in group `vmail`):

```bash
chown -R vmail:vmail /var/lib/xenweet-mailstack/vmail
find /var/lib/xenweet-mailstack/vmail -type d -exec chmod 2770 {} \;
```

Or only fix one domain:

```bash
chown vmail:vmail /var/lib/xenweet-mailstack/vmail/greentalk.xelenic.com
chmod 2770 /var/lib/xenweet-mailstack/vmail/greentalk.xelenic.com
```

Then retry creating the account from the panel.
