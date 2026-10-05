# AGENTS.md

## What this is

A custom fork of the stock OpenCart 3 (OC3) Yandex.Market YML feed extension, packaged as an `.ocmod`. It supports **multiple independent feeds**, each with its own settings and URL. It is **not** a standalone app: there is no build, test, lint, composer, or CI setup. PHP/Twig files only.

## Layout / deployment

- `upload/` mirrors the OpenCart store root. After install, files land at `catalog/...` and `admin/...` paths.
- Deploy = copy/overlay `upload/*` onto an OC3 root (or zip `upload/` as an `.ocmod` and install via Extension Installer), then refresh modifications.
- No `install.xml` / modifications exist yet; this is a plain `upload/` overlay.
- You cannot run or verify anything in this repo without a full OpenCart 3 install with a database. Editing PHP has no local runtime.
- Local syntax check: `php -l <file>` (PHP exists at `C:\php\php.exe` on this workstation, not on PATH).

## Multi-feed architecture

- One row per feed in the table `oc_feed_yandex_market` (`DB_PREFIX . 'feed_yandex_market'`). Columns mirror the form fields plus `feed_id`, `date_added`, `date_modified`. `categories` stays a comma-separated string of category ids. `excluded_products` is a comma-separated list of product ids filtered out in the catalog model `getProduct()`.
- Admin routes (controller `ControllerExtensionFeedYandexMarket`):
  - `extension/feed/yandex_market` → feed list (`yandex_market_list.twig`).
  - `.../add`, `.../edit&feed_id=N`, `.../copy`, `.../delete` → CRUD. Add/edit render `yandex_market.twig` and post a nested `feed[...]` array; the controller `implode`s `feed[categories]` before saving.
  - `.../install`, `.../uninstall` → called by the OC3 marketplace installer (`admin/controller/extension/extension/feed.php`). `uninstall` intentionally keeps feed data.
- Catalog route `extension/feed/yandex_market&feed_id=N` renders one feed. If `feed_id` is omitted it serves the lowest-id active feed (keeps the legacy single-feed URL working). Catalog reads feed settings from the table, not from `config`.
- Admin model = `ModelExportYandexMarket` in `admin/model/export/yandex_market.php`; catalog model is a separate `ModelExportYandexMarket` in `catalog/model/export/yandex_market.php` (`getFeed`/`getDefaultFeed` + product queries).

## Schema init gotcha (overlay deploys)

- `install()` creates the table and migrates legacy settings, but because this repo is deployed as a plain `upload/` overlay onto an **already-installed** extension, `install()` will not run again.
- Therefore the admin `index()` also calls `installSchema()` + `migrateLegacy()` + `ensureModuleStatus()` on every load. `CREATE TABLE IF NOT EXISTS` is idempotent.
- The catalog model also calls `installSchema()` so a feed request before the first admin visit does not error.
- Migration: when `oc_feed_yandex_market` is empty and legacy `feed_yandex_market_*` settings exist, feed #1 is created from them. `ensureModuleStatus()` writes a global `feed_yandex_market_status=1` only if missing, purely so the OC3 Extensions list shows "Enabled".

## Encoding gotcha

- Output YML is **Windows-1251**, hardcoded in `getYml()` (`<?xml ... encoding="windows-1251"?>`).
- All text passes through `prepareField()` / `utf8_to_cp1251()`, which convert UTF-8 source to CP1251 and strip HTML/entities. Keep this pipeline in mind when adding fields or the feed will contain mojibake.
- Per-feed HTML description flag is exposed to `setOffer()` via the controller property `$this->feed` (the `description` flag), not `config`.

## Conventions

- Comments are in Russian in the catalog controller and in the migration/init code; match surrounding style.
- OpenCart loads classes by naming convention (`Controller...`/`Model...`), not autoload/composer. Keep the existing file path ↔ class name mapping.
- Admin sub-method routes are covered by the single permission `extension/feed/yandex_market` (OC3 `ControllerStartupPermission` only checks the first 3 route segments for extension routes).
