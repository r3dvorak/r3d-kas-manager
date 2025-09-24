# R3D KAS Manager

Automation tool for managing **All-Inkl KAS reseller accounts** with batch jobs.

## Features
- Manage domains, mailboxes, DNS entries through KAS SOAP API
- Define reusable automation **recipes** (e.g., domain setup, mailbox with forwards)
- Dry-run mode to preview API actions
- Laravel 11 application (developed under Laragon)

## Requirements
- PHP 8.3+
- Laravel 11
- MySQL 8+
- Laragon (or any local dev stack)
- Composer

## Installation
```bash
git clone https://github.com/r3dvorak/r3d-kas-manager.git
cd r3d-kas-manager
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Usage

List available recipes: 
```bash 
php artisan recipe:list 
```

Run a recipe (dry mode): 
```bash 
php artisan recipe:run 1 --domain=r3d.de --account=w01e77bc --dry
```

## 📜 License

This project is licensed under the **MIT License**.  
See the [LICENSE](LICENSE) file or the full text at [opensource.org/licenses/MIT](https://opensource.org/licenses/MIT).

Copyright (c) 2025 Richard Dvořák, R3D Internet Dienstleistungen

---

## 🧩 Attribution

Built with [Laravel](https://laravel.com) — an open-source PHP framework licensed under the [MIT License](https://opensource.org/licenses/MIT).


