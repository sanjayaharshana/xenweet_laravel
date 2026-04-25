<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Xenweet

Web-based **hosting control panel** and dashboard for managing hosting accounts, built on [Laravel](https://laravel.com).

---

## Server deployment

**Before** installing the application on a production host, read **[INSTALLATION.md](INSTALLATION.md)**. It covers **server access** (SSH, sudo, firewall, DNS), **system requirements** (PHP 8.3+, Nginx, database, etc.), **related software** (Certbot, OpenSSL, Node for the Vite build), and **commands to run on the server** (packages, optional sudo helper scripts, firewall).

---

## About This Project

**Xenweet** is a panel for creating and operating **hosting** accounts: each account has a domain, paths, and optional system integration. Per-hosting features are exposed from a **host panel** and implemented as Laravel **modules** ([nwidart/laravel-modules](https://github.com/nWidart/laravel-modules)):

- **Hosting** — list/create hosting records and open the per-domain panel  
- **File manager** — browse, upload, and edit files under the account  
- **Manage DB** — MySQL databases, users, and [Adminer](https://www.adminer.org/)-backed access  
- **SSL / TLS** — keys, CSRs, manual certificate install, and **Let’s Encrypt (Auto SSL)** with Nginx and optional certbot helpers  
- **SSH access** — connection info, optional jailed account helpers, and a web **terminal** where supported  
- **PHP version** — per-account **web** (Nginx → PHP-FPM) selection; does not change CLI PHP in SSH by itself  

The application expects to run on (or to manage) a typical **Linux** stack: **Nginx** + **PHP-FPM**, optional **Certbot** for HTTP-01 validation, and shell scripts under `scripts/` (including optional `sudo` wrappers for the PHP user) for vhost and SSL install. `INSTALLATION.md` and `.env.example` document the main environment variables.

Laravel is used for routing, authentication, configuration, and the UI. For framework topics, see the [Laravel documentation](https://laravel.com/docs).

### Security

The panel can run shell and server integration on behalf of logged-in users. Restrict who can sign in, keep `.env` and credentials secret, and only enable sudo helpers and terminal features when you understand the server impact.

For security issues in **this** application, report them to the project maintainers (not to the generic Laravel security contact for framework bugs).

## License

Open-source software under the [MIT license](https://opensource.org/licenses/MIT) unless noted otherwise in bundled components.
