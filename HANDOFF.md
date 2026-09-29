# Handoff: Area/Koordinator hierarchy feature (brainstorming, not started)

**Generated**: 2026-09-18
**Branch**: main
**Status**: In Progress — brainstorming phase, no code written yet

## Goal

Add an `area` field to `User`. Relabel roles: `admin` → "Koordinator Area" (UI label only, TBD if DB value changes), `sales` → "Sales". A Koordinator has many Sales under them (grouped by matching `area`, not an explicit FK). Superadmin should be able to see performance broken down: per area, per Koordinator, and per Sales under each Koordinator.

This is classified as an **architectural** change (superpowers:brainstorming skill, architectural path) because it changes what the `admin` role means across nearly every `admin/*` page — currently `admin` = unscoped/sees-everything; this feature scopes it to one area. Process being followed: clarifying questions → 2-3 approaches → sectioned design → written spec (`docs/superpowers/specs/...`) → `writing-plans` skill → implementation. **We are still in the clarifying-questions step.** No design has been proposed yet, no spec file written.

## Completed

- [x] Previous unrelated feature (admin soft-delete for Store/Product) — fully implemented, tested (18/18 passing), committed as `4c973ff`, pushed to `main`. Not part of this handoff's scope, mentioned only for context continuity.
- [x] Classified this request as architectural (superpowers:brainstorming skill).
- [x] Clarifying question 1 answered: **Product catalog stays shared master data, superadmin-only.** Koordinator Area loses Product management entirely (currently `Admin\Products` Livewire component + `admin/produk` route allow both `admin` and `superadmin` — this needs to become superadmin-only).
- [x] Clarifying question 2 answered: **Area is a hardcoded list** (not a DB-managed master table) of East Java cities/regencies: Blitar, Kediri, Pasuruan, Malang, Tulungagung, "dan kota2 lain di Jawa Timur" (user did not give the full exhaustive list — needs to be finalized, see Not Yet Done). Dropdown must be **searchable**, and the user's own answer specified the implementation preference: native `<input list="">` (HTML `<datalist>`), no JS library — matches this project's "native platform feature first" convention (see CLAUDE.md design-system notes). Server-side validation should still enforce the value is one of the allowed list (`in:` rule or similar), since datalist doesn't hard-enforce.
- [x] Clarifying question 3 answered: **Koordinator↔Sales relationship is implicit.** Both Koordinator and Sales get an `area` column; "sales under koordinator X" = `User::where('role','sales')->where('area', $koordinator->area)`. No new relationship table, no `coordinator_id` FK. This was explicitly confirmed by the user ("cukup implisit").

## Not Yet Done

- [ ] **Pending user answer** (last message sent, awaiting reply): confirm that for ALL existing admin pages — `admin/toko` (Stores), `admin/sales` (Sales), `admin/report` (Reports), `admin/performa` (Performance), `admin/peta` (MapView), `admin/` (Dashboard) — Koordinator Area becomes scoped to their own area only, while superadmin keeps seeing everything plus gets new rollup views (per-area, per-koordinator, per-sales-under-koordinator). This is the single biggest scope-defining question in the whole feature — everything downstream depends on the answer.
- [ ] Ask: does `Store` need its own `area` column (persisted at creation), or is a store's effective area derived dynamically via `creator->area` (join through `stores.created_by`)? Tradeoff: derived-via-join is simpler/YAGNI (no new column, no backfill) but means a store's "area" silently drifts if the creating sales rep's area is later changed — historical reports for that store would retroactively shift area. Persisted column is more correct for history but needs a value at store-creation time and a backfill migration for existing stores. **Not yet asked — should be asked before finalizing the schema section of the design.**
- [ ] Ask: does the `role` DB column change values (e.g. `admin` → `koordinator`), or stay `admin`/`sales`/`superadmin` internally with only UI labels changing to "Koordinator Area"/"Sales"? Leaning toward **keeping DB values unchanged** (far less invasive — `User::isAdmin()`, the `can:admin` Gate, and every `abort_unless(...->isAdmin())` check across `StoreCreate`, `VisitForm`, etc. keep working with zero risk), but this hasn't been explicitly confirmed with the user yet.
- [ ] Ask: what happens to a sales rep or koordinator who has **no area assigned** (nullable at first, migration of existing seeded users) — do existing seeded users (`admin@ministo.test`, `budi@ministo.test`, `sari@ministo.test`) get a default area, or does area start nullable and get backfilled manually per user by superadmin after migration?
- [ ] Ask: should `admin/sales` (currently: superadmin+admin can manage ALL sales, see `app/Livewire/Admin/Sales.php`) let a Koordinator only manage sales **in their own area** (create/edit/deactivate), or is sales-user management superadmin-only too (symmetric with the Products decision)?
- [ ] Get the **full, exact list** of Jawa Timur cities/regencies to hardcode — user gave 5 examples (Blitar, Kediri, Pasuruan, Malang, Tulungagung) and said "kota2 yang ada di jawatimur" (implying all of them) — need the complete list of ~38 kabupaten/kota before writing the design's data section. Don't guess/fabricate this list without user confirmation.
- [ ] Propose 2-3 approaches (e.g., how area-scoping is implemented across queries — a query scope/trait vs. per-page manual `->where('area', ...)`, whether `Store` gets a persisted area column, etc.) with tradeoffs, get user's pick.
- [ ] Present sectioned design in chat, get approval section by section.
- [ ] Write spec to `docs/superpowers/specs/2026-09-18-area-koordinator-design.md` (or later date if this resumes another day — use the actual resume date), commit it.
- [ ] Self-review spec (placeholder scan, internal consistency, scope check, ambiguity check).
- [ ] Ask user to review the written spec file.
- [ ] Only after spec approval: invoke `superpowers:writing-plans` skill to produce the implementation plan (do NOT skip straight to code — this is explicitly an architectural-path task per the brainstorming skill's hard gate).

## Failed Approaches (Don't Repeat These)

None — this feature hasn't reached implementation yet, so nothing has been tried and abandoned. (The unrelated soft-delete feature earlier in this session did hit and fix two real bugs — see "Warnings" below, kept for context since they touch the same models this feature will extend.)

## Key Decisions

| Decision | Rationale |
|----------|-----------|
| Architectural brainstorming path (not bounded) | `admin` role currently has zero scoping anywhere in the codebase; this feature changes that meaning across ~6 existing pages simultaneously — too broad and interface-altering to treat as a bounded change. |
| Product catalog: superadmin-only, no area scoping | Explicit user answer — products are shared master data across all areas. |
| Area list: hardcoded (not a DB table) | Explicit user answer: "hard code aja, sementara" — treat as a deliberate YAGNI/interim choice, not a permanent architectural stance. Revisit if the user later wants superadmin to manage the area list dynamically. |
| Area dropdown: native HTML `<datalist>`, no JS library | User's own stated preference; also matches this codebase's established pattern of avoiding new dependencies for things native HTML/CSS can do (see `wire:confirm` choice in the previous soft-delete feature, and CLAUDE.md's general minimal-JS bias). |
| Koordinator↔Sales via matching `area` column, no relationship table | Explicit user answer: "cukup implisit". Simpler schema, no migration/backfill of a pivot or FK, matches the stated goal directly (grouping is just a `WHERE area = ?` away). |

## Current State

**Working**: Entire existing app is untouched — this session made zero code changes for this feature. The previously-committed soft-delete feature (commit `4c973ff`) is deployed to `main` and fully working/tested.

**Broken**: N/A — no code written yet for this feature.

**Uncommitted Changes**: None. `git status` is clean.

## Files to Know (for when implementation starts)

| File | Why It Matters |
|------|----------------|
| `app/Models/User.php` | Will need `area` added to `$fillable`, plus likely a `scopeInArea()` or similar helper. `isAdmin()`/`isSuperadmin()` live here — decide whether role semantics change here. |
| `app/Providers/AppServiceProvider.php` | Defines the `admin`/`superadmin` Gates used as route middleware (`can:admin`) — any new area-scoping Gate/policy would likely live here too. |
| `routes/web.php` | The `admin/*` route group (`can:admin` middleware) covering Dashboard, MapView, Performance, Reports, Stores, Products, Sales, Users — every one of these routes' controllers/components needs the area-scoping decision applied consistently once made. |
| `app/Livewire/Admin/Performance.php` | Current performance page — queries ALL users with zero area filter (`User::query()->orderBy('name')->get()`, see lines 57-69). This is the page most directly extended by the "per area / per koordinator / per sales" rollup goal. |
| `app/Livewire/Admin/Sales.php` | Current sales management — queries ALL sales (`User::where('role','sales')`, line 118), no area filter. Needs the area-scoping decision (pending question above). |
| `app/Livewire/Admin/Stores.php`, `app/Livewire/Admin/Products.php` | Both recently touched by the soft-delete feature (see below) — already have a `$trashed` toggle pattern and `wire:confirm` delete/restore that any new area-filter UI should follow visually (plain colored text-links in action cells, no new button classes). |
| `app/Models/Store.php` | `created_by` → sales rep (`creator()` relation). If Store ends up needing its own `area` column (pending question above), this is where the relation/column would be added. `currentStock()` here is reused elsewhere — don't break it. |
| `resources/css/app.css` | Design tokens (`--color-daun`, `--color-kunyit`, `--color-bata`, etc.) — any new UI (area dropdown, koordinator badge) should reuse existing `.kartu`/`.isian`/`.tombol` classes, not invent new ones (established project convention, reinforced in the previous soft-delete work). |

## Code Context

**Current role check** (`app/Models/User.php:44-52`):
```php
public function isAdmin(): bool
{
    return in_array($this->role, ['admin', 'superadmin'], true);
}

public function isSuperadmin(): bool
{
    return $this->role === 'superadmin';
}
```
No area concept exists anywhere yet. `role` column is a plain string (check migration `0001_01_01_000000_create_users_table.php` for exact type/values — not yet re-verified in this session, confirm before writing the design's schema section).

**Current unscoped admin query pattern** (`app/Livewire/Admin/Performance.php:57-69`) — the shape every area-scoped page will need to change to:
```php
$rows = User::query()
    ->orderBy('name')
    ->get()
    ->map(fn (User $user) => [ ... ])
    ->filter(fn ($row) => $row['user']->role === 'sales' || $row['visits'] > 0)
    ->sortByDesc('sold');
```

**Pending question asked verbatim to user** (awaiting reply as of this handoff):
> "Konfirmasi cakupan: untuk semua halaman admin yang ada sekarang (Toko, Sales, Report, Performa, Peta, Dashboard) — Koordinator Area nanti cuma lihat data areanya sendiri (toko/sales/laporan yang areanya cocok), superadmin tetap lihat semua + dapet view rollup baru (per-area, per-koordinator, per-sales-di-bawah-koordinator). Produk dikecualikan (superadmin-only, sudah disepakati). Bener gitu?"

## Resume Instructions

1. Re-read this file, then re-ask (or resend, if the user already answered elsewhere) the pending confirmation question above — don't assume the answer.
2. Continue the brainstorming skill's architectural path from where it left off: still need to ask the 4 remaining open questions listed in "Not Yet Done" (Store's own `area` column vs. derived; `role` DB value rename vs. label-only; default/null area for existing seeded users; whether Sales management (`admin/sales`) becomes area-scoped for Koordinator too). Ask **one at a time**, per the skill's rule.
3. Once all clarifying questions are answered, propose 2-3 approaches (expect the main axis of variation to be: how area-scoping is applied across ~6 admin pages — a shared query scope/trait on `User`/`Store` vs. manual `->where('area', ...)` repeated per page).
4. Present the design in sections, get approval per section, then write the spec to `docs/superapowers/specs/YYYY-MM-DD-area-koordinator-design.md` (fix the typo — should be `docs/superpowers/specs/`), self-review it, get user sign-off on the spec file itself.
5. Only then invoke `superpowers:writing-plans` to produce the implementation plan — do not write code before that.
6. Docker environment for testing once implementation starts: `docker compose up -d`, then `docker compose exec app php artisan migrate`, tests via `docker compose exec app php artisan test` (MySQL-backed, not SQLite — see CLAUDE.md). Test DB `ministo_test` must exist (`CREATE DATABASE IF NOT EXISTS ministo_test; GRANT ALL ON ministo_test.* TO 'ministo'@'%';` via `docker compose exec db mysql -uroot -psecret`).

## Warnings

- Do not skip straight to writing code or a migration for this feature — the user explicitly said "possible? jangan code dlu" (is this possible? don't code yet) when first raising it, and the brainstorming skill's hard gate applies: no implementation action until the user has approved a design AND (for architectural work) a written spec.
- The previous soft-delete feature (commit `4c973ff`, same session) hit a real gotcha worth remembering if this feature also adds any DB-level `unique` columns scoped by a nullable/soft-delete-like condition: a plain Laravel `Rule::unique()->where(...)` validation scope does NOT change the underlying DB unique index — had to migrate `products.sku` from a plain unique index to a composite `unique(sku, deleted_at)` index because the physical constraint still fired on INSERT even though app-level validation was scoped correctly. If Area ever gets a uniqueness constraint (e.g., "one koordinator per area" enforced at DB level) apply the same lesson: check the actual index, not just the validation rule.
- Also from that session: Livewire's `Livewire::test()->call(...)` disables middleware for the simulated sub-request, so `assertSessionHas()` on flash messages set inside a component action **does not work in tests** even though the same flash pattern works fine in the real browser (full middleware stack). Don't waste time debugging this if it resurfaces — assert on DB/state side-effects instead of session flash in tests.
- `git status` is clean and `main` is up to date with origin as of this handoff — no branch/worktree complications to worry about when resuming.
