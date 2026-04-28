# ZeeBroo Mail First-Time Install Guide

This guide helps you install and validate ZeeBroo Mail on a fresh Ubuntu + Nginx server.

## 1) Prerequisites

- Ubuntu server with Nginx + PHP-FPM (recommended PHP 8.4).
- Laravel app deployed and working.
- Dovecot installed and running.
- Mail stack module enabled in app config.

Check basics:

```bash
php -v
sudo systemctl status nginx --no-pager
sudo systemctl status php8.4-fpm --no-pager
sudo systemctl status dovecot --no-pager
```

## 2) Install PHP IMAP extension

```bash
sudo apt update
sudo apt install -y php8.4-imap
sudo phpenmod -v 8.4 imap
sudo systemctl restart php8.4-fpm nginx
php -m | grep -i '^imap$'
```

If your web app uses another PHP-FPM version, install/restart the matching version too.

## 3) Configure ZeeBroo IMAP env values

Add these values in `.env`:

```env
IMAP_HOST=127.0.0.1
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
ZEEBROO_MAIL_DISABLE_CONNECTIONS=false
```

Then reload Laravel config:

```bash
cd /var/www/xenweet_laravel
php artisan optimize:clear
```

## 4) Verify IMAP service and port

```bash
sudo ss -ltnp | grep :993
openssl s_client -connect 127.0.0.1:993 -brief
```

Expected: Dovecot is listening on `:993` and TLS handshake succeeds.

## 5) Mail stack storage paths

ZeeBroo provisioning can write mailbox auth/maildir into host-local path:

- `<host_root>/mail/dovecot/users.passwd`
- `<host_root>/mail/vmail/<domain>/<local>/Maildir`

If Dovecot passdb is configured to read a global path (for example `/var/lib/xenweet-mailstack/dovecot/users.passwd`), make sure both sides are aligned:

- Either update Dovecot passdb to the active file path.
- Or sync/link the active host-local `users.passwd` to the path Dovecot uses.

Check where mailbox was provisioned:

```bash
sudo grep -R "^user@domain\.com:" /var/lib/xenweet-mailstack /var/www /home 2>/dev/null
```

## 6) Fix permissions for Dovecot auth file

Dovecot must traverse parent directories and read `users.passwd`.

Recommended minimum:

```bash
sudo chmod 755 /var/lib/xenweet-mailstack
sudo chmod 755 /var/lib/xenweet-mailstack/dovecot
sudo chmod 644 /var/lib/xenweet-mailstack/dovecot/users.passwd
sudo systemctl restart dovecot
```

If `users.passwd` is a symlink, apply permissions to the target:

```bash
readlink -f /var/lib/xenweet-mailstack/dovecot/users.passwd
sudo chmod 644 "$(readlink -f /var/lib/xenweet-mailstack/dovecot/users.passwd)"
sudo systemctl restart dovecot
```

Validate path permissions:

```bash
namei -l /var/lib/xenweet-mailstack/dovecot/users.passwd
```

## 7) Create mailbox from panel

In Host Panel:

1. Go to **Email Manager**.
2. Create mailbox (example `user@domain.com`) with password.
3. Open ZeeBroo Mail for that mailbox.

If login fails, verify mailbox row exists in active passwd file:

```bash
sudo grep "^user@domain\.com:" /var/lib/xenweet-mailstack/dovecot/users.passwd
```

If missing, recreate mailbox from panel (delete + create) and check again.

## 8) Diagnose auth failures quickly

Watch Dovecot logs while attempting login:

```bash
sudo journalctl -u dovecot -f
```

Common errors:

- `Permission denied` -> directory/file mode ownership issue.
- `Temporary authentication failure` -> auth backend unreadable/unavailable.
- `AUTHENTICATIONFAILED` -> mailbox password mismatch or wrong passwd file path.

## 9) Diagnose 504 timeout quickly

If ZeeBroo page returns 504:

```bash
sudo tail -n 120 /var/log/nginx/error.log
sudo journalctl -u php8.4-fpm -n 120 --no-pager
tail -n 120 /var/www/xenweet_laravel/storage/logs/laravel.log
```

If you need temporary non-blocking UI access during setup:

```env
ZEEBROO_MAIL_DISABLE_CONNECTIONS=true
```

Then:

```bash
php artisan optimize:clear
sudo systemctl restart php8.4-fpm nginx
```

Set it back to `false` after mail stack is ready.

## 10) Final validation checklist

- PHP IMAP extension is enabled.
- Dovecot listens on port 993.
- `.env` IMAP values are correct.
- Dovecot passdb points to the correct `users.passwd`.
- Dovecot can read `users.passwd`.
- Mailbox row exists for the user.
- ZeeBroo can open folders and read messages.

---

If your setup hosts multiple domains with per-host mail roots, define and document one consistent passdb strategy (central file, generated include files, or SQL passdb) before production rollout.
