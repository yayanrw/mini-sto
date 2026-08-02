# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Mini Sales Taking Order (mini-sto) — consignment product distribution tracking. Sales reps visit stores, record remaining stock and new stock drop-offs; owners/admins see everything on a map, dashboard, and reports.

Laravel 12 · Livewire 3 (full-page components, no SPA framework) · Tailwind 4 · MySQL 8 · Leaflet/OpenStreetMap · PHP 8.2. All UI copy and code comments are in Indonesian.

## Commands

Everything runs through Docker Compose (`app`, `vite`, `db` services — app on :8000, vite on :5173, mysql on :3307):

```bash
docker compose up -d
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link       # required once for store/visit photos to serve

docker compose exec app php artisan test                # full suite (needs ministo_test DB, see below)
docker compose exec app php artisan test --filter=TestName
docker compose exec app php artisan test tests/Feature/RecordVisitTest.php

docker compose run --rm vite npm run build               # production asset build (public/build is committed, NOT gitignored — cPanel has no npm)
docker compose exec app vendor/bin/pint                  # code style (Laravel Pint)
```

Test database is created once, manually:

```bash
docker compose exec db mysql -uroot -psecret \
  -e "CREATE DATABASE IF NOT EXISTS ministo_test; GRANT ALL ON ministo_test.* TO 'ministo'@'%';"
```

Tests deliberately run against **MySQL, not SQLite** (`phpunit.xml`) — bugs like `ONLY_FULL_GROUP_BY` violations only surface on MySQL and several queries in this codebase rely on MySQL-specific grouping behavior.

Seeded accounts (password `password` for all): `super@ministo.test` (superadmin), `admin@ministo.test` (admin), `budi@ministo.test` / `sari@ministo.test` (sales).

## Architecture

### Routing = two apps in one

`routes/web.php` has two route groups sharing one `auth` middleware, split by URL shape rather than role middleware:

- Root group (`/`, `toko/*`, `kunjungan`, `profil`) — the sales-facing mobile UI, bottom tab nav (`components/layout.blade.php`).
- `admin/*` group, gated by `can:admin` — the desktop admin UI, top nav.

Critically, **admins are also allowed into several sales-facing routes** (`stores.create`/`stores.edit`, i.e. the `StoreCreate` component) — the same Livewire component serves both audiences with behavior branching on `auth()->user()->isAdmin()` inside the component and blade (required fields, map interactivity, redirect target). See "Shared components" below before assuming a sales-namespaced component is sales-only.

### The stock model — no stock table

There is no `stock` or `inventory` table. Current stock per store is derived from the **latest visit's items** (`Store::currentStock()` reads `latestVisit->items`). Each `visit_items` row is a full snapshot for that visit:

| Column | Meaning |
|---|---|
| `qty_before` | stock left after the *previous* visit |
| `qty_found` | what the sales rep counted on-site |
| `qty_sold` | `qty_before - qty_found` (derived, not user-entered) |
| `qty_added` | new stock dropped off this visit |
| `qty_left` | `qty_found + qty_added` — becomes next visit's `qty_before` |

**`app/Actions/RecordVisit.php` is the only way to write a visit.** All invariants live there and nowhere else:
- Row-locks the store (`lockForUpdate`) so two sales reps can't race on the same baseline.
- Rejects `qty_found > qty_before` per product.
- Every product with `qty_before > 0` on the current baseline **must** be re-counted in the new visit (`assertNothingLeftOut`) — a visit is a complete snapshot, not a diff, because the next visit's baseline depends on it being complete.
- Runs inside a DB transaction; called via `app(RecordVisit::class)($store, $user, $lines, $gps, $note)`.

Don't write `visit_items` rows anywhere else, including seeders/tests — go through this action (see `tests/Feature/RecordVisitTest.php` for the pattern).

### Reports share one query builder

`app/Reports/VisitReport.php` (static `query()` / `totals()` / private `base()`) is used by both the on-screen Report page and `ReportCsvController` so the numbers never diverge. `totals()` is a separate query from `query()` on purpose — the raw-column select needed for the table view would violate `ONLY_FULL_GROUP_BY` if reused for aggregation.

### Roles and authorization

Three roles on `User.role`: `sales`, `admin`, `superadmin`. `User::isAdmin()` is true for both admin and superadmin; `isSuperadmin()` only for superadmin. Gates (`admin`, `superadmin`) are defined in `AppServiceProvider::boot()` and used as route middleware (`can:admin`).

Ownership: `Store.created_by` points at the sales rep who registered the store (nullable, `nullOnDelete` — see `App\Livewire\Admin\Sales` for reassigning a departing rep's stores to someone else, both in bulk and per-store from `admin/toko`). `Visit.user_id` is `restrictOnDelete` — a user with recorded visits cannot be hard-deleted; deactivate (`active = false`) instead. Sales-facing components enforce ownership explicitly, e.g. `VisitForm::mount()` and `StoreCreate::mount()` both `abort_unless($store->created_by === auth()->id() || auth()->user()->isAdmin(), 403)` — this is **not** covered by route model binding or a policy, it's inline per-component.

### Shared components (sales namespace, dual audience)

`App\Livewire\Sales\StoreCreate` handles both `stores.create`/`stores.edit` (sales) and is also reachable by admins (from `admin/toko`'s "+ Tambah Toko" and "Ubah" actions). Behavior differs by role, not just by create-vs-edit:

- Photo and GPS location: **required** for sales creating a store, always **optional** for admins.
- Map pin: **locked** to GPS (`Ambil GPS` button only) for sales; **draggable and click-to-place** for admins.
- Admins get an extra "assign to sales" dropdown (`assignedTo`) that sets `created_by`; sales implicitly assign to themselves.
- Redirect after save differs: sales → `visits.create` (straight into recording a visit); admin → `admin.stores.show`.

When touching this component, check both audiences render/validate correctly — there's no separate admin-only store form.

### Photo storage

Store and visit photos go to the `public` disk under `stores/` (`$photo->store('stores', 'public')`). Requires `php artisan storage:link`. Uploads use Livewire's `WithFileUploads` with client-side blob preview (Alpine `URL.createObjectURL`) before the actual upload completes — see `store-create.blade.php`'s dropzone for the pattern if adding more photo fields.

### Design system

Single source of truth: `resources/css/app.css` — a `@theme` block of named CSS variables (`--color-kertas`, `--color-tinta`, `--color-daun`, `--color-kunyit`, `--color-bata`) plus hand-rolled component classes (`.kartu`, `.isian`, `.tombol`, `.nota`) rather than utility soup. The visual reference is **nota kembar** (carbon-copy receipt books used by Indonesian corner stores) — the `.nota` component's torn-edge effect is a deliberate nod to this, not decoration to remove. See `docs/ui-restyle-plan.md` for the rationale and contrast measurements behind the palette. New UI should reuse these component classes rather than inventing new ad-hoc Tailwind combinations.

## Deploy

cPanel shared hosting — see `DEPLOY.md`. Because of this, `public/build` (Vite output) is intentionally **not** gitignored: the deploy target has no `npm`, so built assets are committed and uploaded directly.
