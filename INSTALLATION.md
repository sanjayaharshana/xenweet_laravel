# Xenweet Panel — server installation (before you install)

This document describes **what access you need on the server**, **requirements**, **related software** for a fully functional deployment, and **commands to run before** running the Laravel app in production.

The panel is a **Laravel 13** application (PHP **8.3+**) with optional host-level automation (Nginx, Let’s Encrypt, jailed SSH users). Many features assume the app runs **on the same machine** that serves customer sites, with **sudo** configured for specific helper scripts.

---

## 1. Server access you need

| Access | Why |
|--------|-----|
| **SSH** to the server (as a user who can become **root** or use **sudo**) | Install packages, configure Nginx/PHP-FPM, run one-time `sudo` helper installers, and optionally create Linux users for SSH. |
| **Sudo** (or root) for one-time setup | Install system packages, copy scripts to `/usr/local/sbin/`, write `/etc/sudoers.d/*` entries so the **web/PHP user** (often `www-data`) can run **only** whitelisted commands (Nginx reload, `certbot`, jailed user creation) **without a password** (`sudo -n`). |
| **Ability to open firewall ports** | **HTTP 80** (required for Let’s Encrypt HTTP-01), **HTTPS 443**, and **SSH 22** (or your custom SSH port). |
| **DNS under your control** (for each hosted domain) | Point A/AAAA records to this server for sites and for SSL validation. |
| **Write access to the app directory** (deploy user) | `git pull`, `composer install`, `php artisan migrate`, file permissions for `storage/` and `bootstrap/cache/`. |

The **PHP-FPM** process usually runs as **`www-data`** (Debian/Ubuntu). The panel’s automation expects that user to call helpers via `sudo -n` after you run the provided installer scripts once.

---

## 2. Minimum requirements

- **OS**: Linux (tested patterns: Ubuntu/Debian-style; other distros need equivalent packages and paths).
- **PHP**: **8.3+** with extensions typically required by Laravel and this project, including at minimum:
  - `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `bcmath` (if used by dependencies)
- **Web server**: **Nginx** + **PHP-FPM** (Apache can work with equivalent config; this repo includes Nginx-oriented scripts).
- **Database**: **SQLite** (default in `.env.example`) or **MySQL/MariaDB/PostgreSQL** (configure `DB_*` in `.env`).
- **Composer** and **Node.js + npm** (for Vite front-end build: `npm run build`).

---

## 3. Related software (for full functionality)

| Software | Role in this project |
|----------|----------------------|
| **Nginx** | Site vhosts, HTTPS, HTTP→HTTPS, `/.well-known` for Let’s Encrypt. |
| **PHP-FPM** | Runs Laravel; must match the user used in `install-*-sudo.sh` (often `www-data`). |
| **OpenSSL** (`openssl` CLI) | SSL/TLS module: key/CSR generation via helper scripts. |
| **Certbot** | Let’s Encrypt (Auto SSL) when `SSLTLS_LETSENCRYPT_ENABLED=true`. |
| **Bash** | Provisioning and helper scripts under `scripts/`. |
| **(Optional) `chpasswd`, `useradd`, `base64`** | Jailed SSH account creation (`hosting-ssh-create-jailed.sh`) — Linux user management. |

**Panel modules** (Laravel + `nwidart/laravel-modules`) expect:

- `composer dump-autoload` after adding modules (see `modules_statuses.json` and `composer.json` PSR-4 paths).
- Enabled modules: `SshAccess`, `SslTls`, `ManageDb`, `FileManager`, etc.

---

## 4. Commands to run *before* application install (server prep)

Run these as a user with **sudo** on a **Debian/Ubuntu**-style host (adjust package names for RHEL/CentOS).

### 4.1 Base packages

```bash
sudo apt update
sudo apt install -y \
  git curl ca-certificates \
  nginx \
  php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
  php8.3-sqlite3 php8.3-mysql php8.3-bcmath \
  composer \
  openssl \
  certbot
```

(Install the PHP version that matches your policy; the app requires **PHP ≥ 8.3**.)

### 4.2 Node.js (for Vite build)

```bash
# Example using NodeSource or your preferred method; LTS recommended
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

### 4.3 One-time **sudo** helpers (run from the project root *after* code is deployed)

These allow the **web/PHP user** to run only specific commands without an interactive password — required for **Auto SSL**, **Nginx SSL install**, and **jailed SSH user creation** from the panel.

```bash
# Replace www-data with your PHP-FPM pool user if different
sudo bash scripts/install-xenweet-nginx-sudo.sh www-data
sudo bash scripts/install-xenweet-certbot-sudo.sh www-data
sudo bash scripts/install-xenweet-ssh-sudo.sh www-data
```

Re-run these after `git pull` if the underlying scripts in `scripts/` change.

### 4.4 Firewall (example: UFW)

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

---

## 5. Application install (after server prep)

From the project root (as your deploy user):

```bash
cp .env.example .env
php artisan key:generate
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm install
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ensure `storage/` and `bootstrap/cache/` are writable by PHP-FPM, e.g.:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

Point Nginx/PHP to `public/` as the document root and set `APP_URL` in `.env` to your panel URL over **HTTPS** in production.

### 5.1 Queue / scheduler (optional but recommended)

If you use database queues and scheduled `php artisan` tasks, configure a **queue worker** and **cron** for `schedule:run` per Laravel docs.

---

## 6. Security notes

- The **web terminal** and **SSH jailed user** features execute commands or create system users: restrict panel access to **trusted administrators**.
- Use **strong passwords**, **SSH keys**, and keep **.env** and database backups private.
- Review `scripts/install-*.sh` and `/etc/sudoers.d/*` to understand what `www-data` is allowed to run.

---

## 7. See also

- `config/ssltls.php` — Let’s Encrypt and Nginx install paths
- `config/sshaccess.php` — jailed SSH account creation
- `config/hosting_provision.php` — hosting folder / CLI provisioning
- `config/file_manager.php` — file manager constraints

For Laravel-specific topics, see the upstream [Laravel documentation](https://laravel.com/docs).
