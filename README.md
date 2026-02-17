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

## Workspace Rollout / Rollback

### Feature Flag
- `WORKSPACE_ISOLATION_ENABLED=true` enables workspace-based session isolation (`?w=<40hex>`).
- `WORKSPACE_ISOLATION_ENABLED=false` switches back to legacy guard session cookies.

### Runtime Settings (Admin UI: `/config`)
- `workspace_isolation_enabled`: Enable/disable workspace isolation without deployment.
- `workspace_max_active`: Maximum parallel workspaces per user/browser context.
- `workspace_limit_strategy`: `reuse_existing` (default) or `allow_new`.
- `workspace_idle_timeout_minutes`: Idle timeout target for workspace sessions.
- `workspace_absolute_lifetime_hours`: Hard lifetime target for workspace sessions.
- `impersonation_workspace_buffer`: Extra workspace headroom intended for admin impersonation flows.
- `external_launch_token_ttl`: Launch-token TTL in seconds.
- `logout_scope_default`: Default logout scope (`current` or `all`).
- `audit_log_level`: Workspace/launch auditing verbosity preset.

### Legacy Links without `w`
- With workspace isolation enabled, old internal links/forms without `w` are recovered via same-host `Referer` context when possible.
- Login URLs are still canonicalized to include `w` for guest flows.

### Soft Rollout
1. Deploy with `WORKSPACE_ISOLATION_ENABLED=true` on staging.
2. Validate multi-workspace tabs, login/logout, impersonation, and external launch flows.
3. Roll out to production.

### Rollback Strategy
1. Set `WORKSPACE_ISOLATION_ENABLED=false`.
2. Run `php artisan optimize:clear`.
3. Existing sessions continue with legacy cookie behavior; users can re-login if needed.
4. Keep cleanup schedule active (`workspace:cleanup`) to purge stale workspace launch/impersonation artifacts.

## Workspace Operations Guide

### Acceptance / Smoke Test (Phase 8)
1. Open `https://<host>/login?w=<A40HEX>` and login as Admin.
2. Open a second tab with `https://<host>/login?w=<B40HEX>` and login as Client B.
3. Open a third tab with `https://<host>/login?w=<C40HEX>` and login as Client C.
4. Verify each tab keeps its own role/data after navigation and redirects.
5. Verify logout in one workspace does not terminate other workspaces.
6. Verify language switch keeps the same `w` and remains isolated per workspace.

### Browser Matrix (manual)
- Firefox normal profile: pass
- Firefox private profile: pass
- Chrome/Brave normal profile: pass
- Chrome/Brave private profile: pass

### Runbook Commands
```bash
php artisan test
php artisan workspace:cleanup --hours=24
php artisan schedule:run
```

### Troubleshooting
- `419 Page Expired`: URL lost `?w=...` or stale CSRF token; reload current workspace URL and retry.
- `Size of a request header field exceeds server limit`: too many workspace cookies; run cleanup/logout-all and restart browser session.
- Legacy link without `w`: app should canonicalize/recover `w`; if disabled, check `WORKSPACE_ISOLATION_ENABLED`.

## 📜 License

This project is licensed under the **MIT License**.  
See the [LICENSE](LICENSE) file or the full text at [opensource.org/licenses/MIT](https://opensource.org/licenses/MIT).

Copyright (c) 2025 Richard Dvořák, R3D Internet Dienstleistungen

---

## 🧩 Attribution

Built with [Laravel](https://laravel.com) — an open-source PHP framework licensed under the [MIT License](https://opensource.org/licenses/MIT).
