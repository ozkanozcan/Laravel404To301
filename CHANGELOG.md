# Changelog

All notable changes to `laravel-404-to-301` will be documented in this file.

This project adheres to [Semantic Versioning](https://semver.org/) and
[Conventional Commits](https://www.conventionalcommits.org/).

---

## [Unreleased]
 
---

## [1.0.2] — 2026-09-18

### Fixed
- **CI / Composer Policy:** Fixed CI matrix test failures on Laravel 10 and 11 caused by Composer 2.8+ / Packagist `policy.advisories.block` blocking older framework versions with known security advisories. Added `policy.advisories.block: false` in `composer.json` config and `COMPOSER_POLICY_ADVISORIES_BLOCK: 0` in GitHub Actions workflow.

---

## [1.0.1] — 2026-08-11

### Fixed
- **Critical:** Added missing `use Illuminate\Support\Facades\DB;` import in `MissingUrl` model — `DB::raw()` call inside `recordHit()` caused a fatal `Class not found` error at runtime when `db_log_missing` was enabled.
- `SyncRedirectsCommand`: `is_active` field now correctly handles string values from CSV files (`'true'`, `'false'`, `'1'`, `'0'`, `'no'`), previously always cast to `true` for any non-empty string.

### Changed
- `composer.json`: Set `minimum-stability` from `dev` to `stable` — more appropriate for a released library package.

---

## [1.0.0] — 2024-08-11

### Added
- `Handle404Redirect` middleware for automatic 404 interception and 301/302 redirection.
- `Redirect` Eloquent model and `redirects` table migration with hit tracking and active toggles.
- `MissingUrl` Eloquent model and `missing_urls` table migration with hit counter, referrer, IP, and user-agent logging.
- `RedirectService` caching layer with configurable TTL and key prefixes.
- `Redirect404Facade` facade access to underlying service methods.
- `php artisan redirect:missing` command to inspect missing 404 logs.
- `php artisan redirect:sync` command to bulk import CSV/JSON redirect rules.
- `php artisan redirect:prune-missing` command to prune old missing URL records.
- Configurable `ignore_patterns` to skip static asset 404s.
- Dual missing URL logging (Database + Laravel Log channel).
- Language support (`en`, `tr`).
- Full PHPUnit test suite.
- GitHub Actions CI workflow for PHP 8.2/8.3/8.4 and Laravel 10/11/12.

[Unreleased]: https://github.com/ozkanozcan/Laravel404To301/compare/v1.0.2...HEAD
[1.0.2]: https://github.com/ozkanozcan/Laravel404To301/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/ozkanozcan/Laravel404To301/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/ozkanozcan/Laravel404To301/releases/tag/v1.0.0
