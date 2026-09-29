# Area dan Koordinator Area Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tambah `area` pada `User` dan `Store`, batasi Koordinator Area (role `admin`) ke areanya sendiri, dan beri superadmin halaman rollup per area / koordinator / sales.

**Architecture:** Kolom `area` (string, nullable, di-index) di `users` dan `stores`. Daftar 38 area Jawa Timur di satu class konstanta `App\Support\Areas`. Scoping lewat scope eksplisit `visibleTo($viewer)` di `Store` dan `User`, ditambah cek per-record `User::canAccessArea()` di setiap aksi Livewire. `VisitReport` tetap satu-satunya query laporan, sekarang menerima viewer.

**Tech Stack:** Laravel 12, Livewire 3, Tailwind 4, MySQL 8, PHPUnit (tes jalan di MySQL `ministo_test`).

**Spec:** `docs/superpowers/specs/2026-09-25-area-koordinator-design.md` (harus dibaca bersama plan ini).

## Global Constraints

- Semua teks UI dan komentar kode dalam **bahasa Indonesia**. Komentar untuk logika yang tidak jelas (aturan proyek).
- Nilai `users.role` di DB **tidak berubah** (`sales`/`admin`/`superadmin`). Hanya label UI.
- Tes berjalan di **MySQL**, bukan SQLite: `docker compose exec app php artisan test`. Test DB `ministo_test` sudah harus ada (lihat CLAUDE.md).
- Tulis tes dulu (merah), baru implementasi (hijau), per task.
- Daftar area: 29 kabupaten berawalan `Kab. ` dan 9 kota berawalan `Kota `, total 38, sesuai spec.
- `where('area', null)` di Laravel menjadi `IS NULL`. Scope untuk koordinator tanpa area **wajib** mengembalikan kosong (`whereRaw('1 = 0')`).
- `left` adalah reserved word MySQL. Jangan dipakai sebagai alias kolom.
- Ubah class UI yang ada saja (`.kartu`, `.isian`, `.isian-kecil`, `.tombol`, `.label-kecil`), jangan bikin kelas baru. Aksi teks-link mengikuti pola `text-sm text-daun font-medium hover:underline`.
- `public/build` di-commit. Bangun ulang aset setelah blade berubah (Task 8).
- Setiap Bash call diawali `rtk` (aturan global user). Commit hanya setelah user setuju (`/dcp`); langkah "Commit" di bawah adalah titik checkpoint, bukan izin.

## Review Focus

1. Koordinator **tanpa area** melihat data kosong, bukan semua data berarea null (Task 1, 4).
2. Superadmin memindahkan toko ke sales **tanpa area**: `stores.area` jadi null, toko hanya terlihat oleh superadmin (Task 2).
3. Kunjungan dari toko yang sudah **soft-deleted** tetap terhitung di laporan koordinator, sama dengan superadmin (Task 4).
4. **id yang dimanipulasi client** (argumen aksi, `editingId`, `movingId`) ke data area lain menghasilkan 403 (Task 3).
5. Rollup: dua kunjungan dalam detik yang sama (`visited_at` kembar) tidak menghitung stok dua kali; area null tampil "Tanpa area" (Task 7).

## Temuan dari kode (mempengaruhi plan)

- `admin/pengguna` saat ini `can:admin`; hanya nav yang menyembunyikannya. Task 5 mengubah route jadi `can:superadmin`.
- `tests/Feature/AdminSoftDeleteTest.php` memakai user `admin` tanpa area dan menguji Produk. Setelah perubahan, tes itu patah, jadi `setUp` diubah jadi `superadmin` (Task 5).
- Tidak ada `StoreFactory`/`VisitFactory`. Tes memakai `Model::create` (pola yang sudah ada).
- `MapView` me-render marker sekali lewat `wire:ignore`, jadi filter area Peta memakai `<form method="get">` biasa (reload penuh) dengan `#[Url]`, bukan `wire:model.live`.

## File Structure

| File | Aksi | Tanggung jawab |
|---|---|---|
| `database/migrations/2026_01_01_000500_add_area_to_users_and_stores_tables.php` | Create | Kolom `area` + backfill |
| `app/Support/Areas.php` | Create | Daftar 38 area, `all()`, `rule()` |
| `app/Models/User.php`, `app/Models/Store.php` | Modify | `area` fillable, `roleLabel`, `canAccessArea`, `visibleTo`, `visibleIds`, `areaSuffix` |
| `app/Reports/VisitReport.php` | Modify | Param viewer + filter `area` |
| `app/Reports/AreaRollup.php` | Create | Angka rollup per area/koordinator/sales |
| `app/Livewire/Admin/{Stores,Sales,StoreShow,Users,Dashboard,MapView,Performance,Reports}.php` | Modify | Scope + cek per-record |
| `app/Livewire/Admin/Areas.php` | Create | Halaman rollup |
| `app/Livewire/Sales/{StoreCreate,VisitForm}.php` | Modify | Salin area, cek area |
| `app/Http/Controllers/ReportCsvController.php` | Modify | Kirim viewer |
| `routes/web.php` | Modify | `produk`, `pengguna`, `area` jadi superadmin |
| `resources/views/components/{area-input,area-filter}.blade.php` | Create | Datalist dan dropdown filter |
| `resources/views/components/layout.blade.php` | Modify | Nav |
| `resources/views/livewire/admin/*.blade.php` | Modify/Create | Kolom, filter, rollup |
| `tests/Feature/Concerns/BuildsAreaFixtures.php` | Create | Helper fixture |
| `tests/Feature/{AreaFoundation,AreaCopy,AreaAccess,AreaReports,AreaRoutes,AreaUi,AreaRollup}Test.php` | Create | Tes per task |
| `tests/Feature/AdminSoftDeleteTest.php` | Modify | `setUp` pakai superadmin |
| `CLAUDE.md` | Modify | Catatan area (Task 8) |

---

### Task 1: Fondasi (migration, Areas, model)

**Files:**
- Create: `database/migrations/2026_01_01_000500_add_area_to_users_and_stores_tables.php`, `app/Support/Areas.php`, `tests/Feature/Concerns/BuildsAreaFixtures.php`, `tests/Feature/AreaFoundationTest.php`
- Modify: `app/Models/User.php`, `app/Models/Store.php`

**Interfaces:**
- Produces:
  - `Areas::all(): array<int,string>` (38 nilai terurut), `Areas::rule(): \Illuminate\Validation\Rules\In`
  - `User::isKoordinator(): bool`, `User::roleLabel(): string`, `User::canAccessArea(?string $area): bool`, `User::areaSuffix(): string` (mis. `" · Kab. Malang"`, string kosong kalau bukan koordinator/tanpa area), `User::scopeVisibleTo(Builder $q, User $viewer): Builder`
  - `Store::scopeVisibleTo(Builder $q, User $viewer): Builder`, `Store::visibleIds(User $viewer): Builder` (subquery `select stores.id`, termasuk toko soft-deleted)
  - Trait `Tests\Feature\Concerns\BuildsAreaFixtures`: `user(string $role, ?string $area = null): User`, `store(?string $area, ?User $creator = null, string $name = 'Toko'): Store`, `product(): Product`, `visit(Store $store, User $by, int $found = 0, int $added = 10): Visit` (lewat `RecordVisit`)

- [ ] **Step 1: Tulis helper fixture**

`tests/Feature/Concerns/BuildsAreaFixtures.php`:

```php
<?php

namespace Tests\Feature\Concerns;

use App\Actions\RecordVisit;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;

trait BuildsAreaFixtures
{
    private int $fixtureSeq = 0;

    protected function user(string $role, ?string $area = null): User
    {
        $n = ++$this->fixtureSeq;

        return User::create([
            'name' => ucfirst($role)." {$n}",
            'email' => "{$role}{$n}@test.local",
            'password' => 'password',
            'role' => $role,
            'area' => $area,
        ]);
    }

    protected function store(?string $area, ?User $creator = null, string $name = 'Toko'): Store
    {
        return Store::create([
            'name' => $name,
            'area' => $area,
            'created_by' => $creator?->id,
        ]);
    }

    protected function product(): Product
    {
        return Product::firstOrCreate(['sku' => 'KRP-1'], ['name' => 'Keripik', 'unit' => 'bungkus']);
    }

    /** Catat kunjungan lewat RecordVisit (satu-satunya jalur tulis yang sah). */
    protected function visit(Store $store, User $by, int $found = 0, int $added = 10): Visit
    {
        return app(RecordVisit::class)($store, $by, [
            ['product_id' => $this->product()->id, 'qty_found' => $found, 'qty_added' => $added],
        ]);
    }
}
```

- [ ] **Step 2: Tulis tes gagal**

`tests/Feature/AreaFoundationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Support\Areas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaFoundationTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    public function test_daftar_area_38_unik_dan_membedakan_kab_dan_kota(): void
    {
        $all = Areas::all();

        $this->assertCount(38, $all);
        $this->assertCount(38, array_unique($all));
        $this->assertContains('Kab. Malang', $all);
        $this->assertContains('Kota Malang', $all);
    }

    public function test_role_label(): void
    {
        $this->assertSame('Koordinator Area', $this->user('admin')->roleLabel());
        $this->assertSame('Sales', $this->user('sales')->roleLabel());
        $this->assertSame('Superadmin', $this->user('superadmin')->roleLabel());
    }

    public function test_can_access_area(): void
    {
        $koor = $this->user('admin', 'Kab. Malang');

        $this->assertTrue($koor->canAccessArea('Kab. Malang'));
        $this->assertFalse($koor->canAccessArea('Kota Malang'));
        $this->assertFalse($koor->canAccessArea(null));
        $this->assertFalse($this->user('admin')->canAccessArea(null), 'koordinator tanpa area tidak boleh apa pun');
        $this->assertFalse($this->user('sales', 'Kab. Malang')->canAccessArea('Kab. Malang'));
        $this->assertTrue($this->user('superadmin')->canAccessArea(null));
    }

    public function test_store_visible_to(): void
    {
        $a = $this->store('Kab. Malang', null, 'A');
        $b = $this->store('Kota Kediri', null, 'B');
        $n = $this->store(null, null, 'N');

        $names = fn (User $u) => Store::visibleTo($u)->orderBy('name')->pluck('name')->all();

        $this->assertSame(['A'], $names($this->user('admin', 'Kab. Malang')));
        // Koordinator tanpa area: kosong, BUKAN toko berarea null.
        $this->assertSame([], $names($this->user('admin')));
        $this->assertSame(['A', 'B', 'N'], $names($this->user('superadmin')));
        $this->assertSame([], $names($this->user('sales', 'Kab. Malang')));
    }

    public function test_user_visible_to_hanya_sales_di_area_koordinator(): void
    {
        $inArea = $this->user('sales', 'Kab. Malang');
        $this->user('sales', 'Kota Kediri');
        $this->user('admin', 'Kab. Malang'); // koordinator lain di area sama: bukan sales
        $this->user('sales'); // tanpa area

        $ids = User::visibleTo($this->user('admin', 'Kab. Malang'))->pluck('id')->all();

        $this->assertSame([$inArea->id], $ids);
        $this->assertSame([], User::visibleTo($this->user('admin'))->pluck('id')->all());
    }

    public function test_visible_ids_menyertakan_toko_yang_dihapus(): void
    {
        $store = $this->store('Kab. Malang');
        $store->delete();

        $ids = Store::visibleIds($this->user('admin', 'Kab. Malang'))->pluck('id')->all();

        $this->assertSame([$store->id], $ids);
    }

    public function test_area_suffix(): void
    {
        $this->assertSame(' · Kab. Malang', $this->user('admin', 'Kab. Malang')->areaSuffix());
        $this->assertSame('', $this->user('admin')->areaSuffix());
        $this->assertSame('', $this->user('superadmin', 'Kab. Malang')->areaSuffix());
    }
}
```

- [ ] **Step 3: Jalankan, pastikan gagal**

Run: `docker compose exec app php artisan test tests/Feature/AreaFoundationTest.php`
Expected: FAIL (`Class "App\Support\Areas" not found` / kolom `area` tidak ada).

- [ ] **Step 4: Migration**

`database/migrations/2026_01_01_000500_add_area_to_users_and_stores_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('area')->nullable()->index()->after('phone');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->string('area')->nullable()->index()->after('address');
        });

        // Toko lama mewarisi area pembuatnya. Saat migrasi pertama semua user
        // berarea null, jadi ini tidak mengubah apa pun; tetap aman dijalankan ulang.
        DB::statement('UPDATE stores JOIN users ON users.id = stores.created_by SET stores.area = users.area');
    }

    public function down(): void
    {
        Schema::table('stores', fn (Blueprint $table) => $table->dropColumn('area'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('area'));
    }
};
```

- [ ] **Step 5: `Areas`**

`app/Support/Areas.php`:

```php
<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Daftar area (kabupaten/kota Jawa Timur). Sengaja hardcode, bukan tabel:
 * cukup untuk sekarang, pindah ke tabel kalau superadmin perlu mengelolanya.
 */
class Areas
{
    private const KABUPATEN = [
        'Bangkalan', 'Banyuwangi', 'Blitar', 'Bojonegoro', 'Bondowoso', 'Gresik', 'Jember', 'Jombang',
        'Kediri', 'Lamongan', 'Lumajang', 'Madiun', 'Magetan', 'Malang', 'Mojokerto', 'Nganjuk', 'Ngawi',
        'Pacitan', 'Pamekasan', 'Pasuruan', 'Ponorogo', 'Probolinggo', 'Sampang', 'Sidoarjo', 'Situbondo',
        'Sumenep', 'Trenggalek', 'Tuban', 'Tulungagung',
    ];

    private const KOTA = [
        'Batu', 'Blitar', 'Kediri', 'Madiun', 'Malang', 'Mojokerto', 'Pasuruan', 'Probolinggo', 'Surabaya',
    ];

    /** @return array<int, string> */
    public static function all(): array
    {
        // Awalan Kab./Kota membedakan area yang namanya sama (mis. Malang).
        $all = [
            ...array_map(fn (string $n) => "Kab. {$n}", self::KABUPATEN),
            ...array_map(fn (string $n) => "Kota {$n}", self::KOTA),
        ];
        sort($all);

        return $all;
    }

    public static function rule(): In
    {
        return Rule::in(self::all());
    }
}
```

- [ ] **Step 6: Model `User`**

Di `app/Models/User.php`: tambah `'area'` ke `$fillable` (setelah `'phone'`), `use Illuminate\Database\Eloquent\Builder;`, dan method berikut setelah `isSuperadmin()`:

```php
    public function isKoordinator(): bool
    {
        return $this->role === 'admin';
    }

    /** Label tampilan; nilai `role` di DB tidak berubah. */
    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Koordinator Area',
            'superadmin' => 'Superadmin',
            default => 'Sales',
        };
    }

    /** Boleh menyentuh data di $area? Tanpa area = tanpa akses (kecuali superadmin). */
    public function canAccessArea(?string $area): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return $this->isKoordinator() && $area !== null && $area === $this->area;
    }

    /** Akhiran judul halaman koordinator, mis. " · Kab. Malang". */
    public function areaSuffix(): string
    {
        return $this->isKoordinator() && $this->area ? " · {$this->area}" : '';
    }

    /** Pengguna yang boleh dilihat $viewer: superadmin semua, koordinator hanya sales di areanya. */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        if ($viewer->isSuperadmin()) {
            return $query;
        }

        // Jangan pakai where('area', null): Laravel mengubahnya jadi IS NULL,
        // dan koordinator tanpa area akan melihat semua data berarea null.
        if (! $viewer->isKoordinator() || $viewer->area === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('users.role', 'sales')->where('users.area', $viewer->area);
    }
```

- [ ] **Step 7: Model `Store`**

Di `app/Models/Store.php`: tambah `'area'` ke `$fillable` (setelah `'address'`) dan `use Illuminate\Database\Eloquent\Builder;` sudah ada. Tambah setelah `scopeNearest`:

```php
    /** Toko yang boleh dilihat $viewer. Lihat catatan null di User::scopeVisibleTo. */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        if ($viewer->isSuperadmin()) {
            return $query;
        }

        if (! $viewer->isKoordinator() || $viewer->area === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('stores.area', $viewer->area);
    }

    /**
     * Subquery id toko yang terlihat, untuk membatasi query kunjungan.
     * withTrashed: kunjungan dari toko yang dihapus tetap terhitung di laporan,
     * sama seperti yang dilihat superadmin.
     */
    public static function visibleIds(User $viewer): Builder
    {
        return static::withTrashed()->visibleTo($viewer)->select('stores.id');
    }
```

- [ ] **Step 8: Jalankan migrate dan tes**

Run: `docker compose exec app php artisan migrate` lalu `docker compose exec app php artisan test tests/Feature/AreaFoundationTest.php`
Expected: PASS (7 tes).

- [ ] **Step 9: Commit checkpoint** — `feat: add area column, Areas list, and visibleTo scopes`

---

### Task 2: Aturan penyalinan area ke toko

**Files:**
- Modify: `app/Livewire/Sales/StoreCreate.php`, `app/Livewire/Admin/Stores.php` (`moveStore`), `app/Livewire/Admin/Sales.php` (`moveStores`)
- Test: `tests/Feature/AreaCopyTest.php`

**Interfaces:**
- Consumes: `User::canAccessArea`, `BuildsAreaFixtures`
- Produces: invarian "`stores.area` = area sales pemilik saat dibuat / dipindah"

- [ ] **Step 1: Tulis tes gagal**

`tests/Feature/AreaCopyTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Livewire\Admin\Sales as AdminSales;
use App\Livewire\Admin\Stores;
use App\Livewire\Sales\StoreCreate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaCopyTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    public function test_sales_membuat_toko_area_disalin_dari_sales(): void
    {
        Storage::fake('public');
        $sales = $this->user('sales', 'Kab. Malang');

        Livewire::actingAs($sales)->test(StoreCreate::class)
            ->set('name', 'Toko Baru')
            ->set('photo', UploadedFile::fake()->image('a.jpg'))
            ->call('setLocation', -7.9, 112.6)
            ->call('save');

        $this->assertDatabaseHas('stores', ['name' => 'Toko Baru', 'area' => 'Kab. Malang', 'created_by' => $sales->id]);
    }

    public function test_admin_membuat_toko_area_ikut_sales_yang_dipilih(): void
    {
        $sales = $this->user('sales', 'Kota Kediri');

        Livewire::actingAs($this->user('superadmin'))->test(StoreCreate::class)
            ->set('name', 'Toko Admin')
            ->set('assignedTo', $sales->id)
            ->call('save');

        $this->assertDatabaseHas('stores', ['name' => 'Toko Admin', 'area' => 'Kota Kediri']);
    }

    public function test_pindah_toko_ke_sales_beda_area_area_toko_ikut_pindah(): void
    {
        $from = $this->user('sales', 'Kab. Malang');
        $to = $this->user('sales', 'Kota Kediri');
        $store = $this->store('Kab. Malang', $from);

        Livewire::actingAs($this->user('superadmin'))->test(Stores::class)
            ->call('startMove', $store->id)
            ->set('moveToId', $to->id)
            ->call('moveStore');

        $this->assertSame('Kota Kediri', $store->fresh()->area);
        $this->assertSame($to->id, $store->fresh()->created_by);
    }

    public function test_pindah_ke_sales_tanpa_area_membuat_area_toko_null(): void
    {
        $from = $this->user('sales', 'Kab. Malang');
        $to = $this->user('sales');
        $store = $this->store('Kab. Malang', $from);

        Livewire::actingAs($this->user('superadmin'))->test(Stores::class)
            ->call('startMove', $store->id)
            ->set('moveToId', $to->id)
            ->call('moveStore');

        $this->assertNull($store->fresh()->area);
    }

    public function test_pindah_massal_menyalin_area_sales_tujuan(): void
    {
        $from = $this->user('sales', 'Kab. Malang');
        $to = $this->user('sales', 'Kota Kediri');
        $a = $this->store('Kab. Malang', $from);
        $b = $this->store('Kab. Malang', $from);

        Livewire::actingAs($this->user('superadmin'))->test(AdminSales::class)
            ->call('startMove', $from->id)
            ->set('moveToId', $to->id)
            ->call('moveStores');

        $this->assertSame('Kota Kediri', $a->fresh()->area);
        $this->assertSame('Kota Kediri', $b->fresh()->area);
    }

    public function test_ubah_area_sales_tidak_mengubah_area_toko_lama(): void
    {
        $sales = $this->user('sales', 'Kab. Malang');
        $store = $this->store('Kab. Malang', $sales);

        $sales->update(['area' => 'Kota Kediri']);

        $this->assertSame('Kab. Malang', $store->fresh()->area);
    }

    public function test_edit_toko_tanpa_ganti_sales_tidak_mengubah_area(): void
    {
        $sales = $this->user('sales', 'Kab. Malang');
        $store = $this->store('Kab. Malang', $sales);
        $sales->update(['area' => 'Kota Kediri']); // area sales berubah setelah toko dibuat

        Livewire::actingAs($this->user('superadmin'))->test(StoreCreate::class, ['store' => $store])
            ->set('name', 'Nama Baru')
            ->call('save');

        $this->assertSame('Kab. Malang', $store->fresh()->area);
        $this->assertSame('Nama Baru', $store->fresh()->name);
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `docker compose exec app php artisan test tests/Feature/AreaCopyTest.php`
Expected: FAIL (kolom `area` toko tidak terisi).

- [ ] **Step 3: `StoreCreate::save()`**

Ganti bagian mulai `$data = $this->validate();` sampai sebelum `if ($this->editing)` dengan:

```php
        $data = $this->validate();
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $attributes = [
            'name' => $data['name'],
            'owner_name' => $data['owner_name'] ?: null,
            'phone' => $data['phone'] ?: null,
            'address' => $data['address'] ?: null,
            'lat' => $data['lat'] !== null && $data['lat'] !== '' ? $data['lat'] : null,
            'lng' => $data['lng'] !== null && $data['lng'] !== '' ? $data['lng'] : null,
        ];

        $assignee = null;

        if ($isAdmin) {
            $assignee = User::where('role', 'sales')->findOrFail($data['assignedTo']);

            // Koordinator hanya boleh menugaskan toko ke sales di areanya sendiri.
            abort_unless($user->canAccessArea($assignee->area), 403);

            $attributes['created_by'] = $assignee->id;
        }

        if ($this->photo) {
            $attributes['photo_path'] = $this->photo->store('stores', 'public');
        }
```

Lalu di cabang `if ($this->editing) {` sebelum `->update($attributes)` tambah:

```php
            // Area toko ikut sales baru hanya saat toko benar-benar berpindah tangan;
            // mengubah area sales tidak menggeser riwayat toko lamanya.
            if ($assignee && $this->editing->created_by !== $assignee->id) {
                $attributes['area'] = $assignee->area;
            }

```

Dan pada `Store::create([...])` tambah baris `'area' => $assignee ? $assignee->area : $user->area,` (di bawah spread `...$attributes`). Hapus deklarasi `$isAdmin` lama yang kini duplikat.

- [ ] **Step 4: `Stores::moveStore()`**

Ganti baris `$store->update(['created_by' => $to->id]);` menjadi:

```php
        // Area toko ikut sales tujuan (null kalau sales tujuan belum punya area).
        $store->update(['created_by' => $to->id, 'area' => $to->area]);
```

- [ ] **Step 5: `Sales::moveStores()`**

Ganti baris `$count = Store::where(...)->update(...)` menjadi:

```php
        $count = Store::where('created_by', $from->id)->update(['created_by' => $to->id, 'area' => $to->area]);
```

- [ ] **Step 6: Jalankan tes**

Run: `docker compose exec app php artisan test tests/Feature/AreaCopyTest.php`
Expected: PASS (7 tes).

- [ ] **Step 7: Commit checkpoint** — `feat: copy area from sales to stores on create and reassign`

---

### Task 3: Scope dan cek per-record (Toko, Sales, StoreShow, StoreCreate, VisitForm)

**Files:**
- Modify: `app/Livewire/Admin/Stores.php`, `Sales.php`, `StoreShow.php`, `app/Livewire/Sales/StoreCreate.php`, `VisitForm.php`
- Test: `tests/Feature/AreaAccessTest.php`

**Interfaces:**
- Consumes: `User::canAccessArea`, `visibleTo`, `BuildsAreaFixtures`
- Produces: perilaku 403 untuk id lintas area di semua aksi.

- [ ] **Step 1: Tulis tes gagal**

`tests/Feature/AreaAccessTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Livewire\Admin\Sales as AdminSales;
use App\Livewire\Admin\Stores;
use App\Livewire\Sales\StoreCreate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaAccessTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    private User $koor;

    private User $salesA;

    private User $salesB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->koor = $this->user('admin', 'Kab. Malang');
        $this->salesA = $this->user('sales', 'Kab. Malang');
        $this->salesB = $this->user('sales', 'Kota Kediri');
    }

    public function test_daftar_toko_hanya_area_koordinator(): void
    {
        $this->store('Kab. Malang', $this->salesA, 'Toko Dalam');
        $this->store('Kota Kediri', $this->salesB, 'Toko Luar');

        Livewire::actingAs($this->koor)->test(Stores::class)
            ->assertSee('Toko Dalam')
            ->assertDontSee('Toko Luar');
    }

    public function test_koordinator_tanpa_area_tidak_melihat_toko_apa_pun(): void
    {
        $this->store(null, null, 'Toko Tanpa Area');

        Livewire::actingAs($this->user('admin'))->test(Stores::class)
            ->assertDontSee('Toko Tanpa Area');
    }

    public function test_aksi_toko_lintas_area_403(): void
    {
        $luar = $this->store('Kota Kediri', $this->salesB);
        $luar->delete();
        $luarAktif = $this->store('Kota Kediri', $this->salesB);

        $c = Livewire::actingAs($this->koor)->test(Stores::class);

        $c->call('edit', $luarAktif->id)->assertForbidden();
        $c->call('delete', $luarAktif->id)->assertForbidden();
        $c->call('restore', $luar->id)->assertForbidden();

        $this->assertNull($luarAktif->fresh()->deleted_at);
    }

    public function test_simpan_toko_dengan_editing_id_dimanipulasi_403(): void
    {
        $luar = $this->store('Kota Kediri', $this->salesB);

        Livewire::actingAs($this->koor)->test(Stores::class)
            ->set('editingId', $luar->id)
            ->set('name', 'Dibajak')
            ->call('save')
            ->assertForbidden();

        $this->assertNotSame('Dibajak', $luar->fresh()->name);
    }

    public function test_pindah_toko_ke_sales_area_lain_ditolak(): void
    {
        $dalam = $this->store('Kab. Malang', $this->salesA);

        Livewire::actingAs($this->koor)->test(Stores::class)
            ->call('startMove', $dalam->id)
            ->set('moveToId', $this->salesB->id)
            ->call('moveStore')
            ->assertNotFound();

        $this->assertSame($this->salesA->id, $dalam->fresh()->created_by);
    }

    public function test_daftar_sales_dan_aksi_lintas_area(): void
    {
        Livewire::actingAs($this->koor)->test(AdminSales::class)
            ->assertSee($this->salesA->name)
            ->assertDontSee($this->salesB->name)
            ->call('edit', $this->salesB->id)
            ->assertForbidden();
    }

    public function test_simpan_sales_dengan_editing_id_dimanipulasi_403(): void
    {
        Livewire::actingAs($this->koor)->test(AdminSales::class)
            ->set('editingId', $this->salesB->id)
            ->set('name', 'Dibajak')
            ->set('email', $this->salesB->email)
            ->call('save')
            ->assertForbidden();
    }

    public function test_koordinator_membuat_sales_area_dipaksa_area_sendiri(): void
    {
        Livewire::actingAs($this->koor)->test(AdminSales::class)
            ->set('name', 'Sales Baru')
            ->set('email', 'baru@test.local')
            ->set('password', 'password123')
            ->set('area', 'Kota Kediri') // dicoba dibelokkan; harus diabaikan
            ->call('save');

        $this->assertDatabaseHas('users', ['email' => 'baru@test.local', 'role' => 'sales', 'area' => 'Kab. Malang']);
    }

    public function test_koordinator_tanpa_area_tidak_bisa_membuat_sales(): void
    {
        Livewire::actingAs($this->user('admin'))->test(AdminSales::class)
            ->set('name', 'Sales Baru')
            ->set('email', 'baru@test.local')
            ->set('password', 'password123')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'baru@test.local']);
    }

    public function test_pindah_massal_ke_sales_area_lain_ditolak(): void
    {
        $this->store('Kab. Malang', $this->salesA);

        Livewire::actingAs($this->koor)->test(AdminSales::class)
            ->call('startMove', $this->salesA->id)
            ->set('moveToId', $this->salesB->id)
            ->call('moveStores')
            ->assertNotFound();
    }

    public function test_store_show_lintas_area_403(): void
    {
        $luar = $this->store('Kota Kediri', $this->salesB);
        $dalam = $this->store('Kab. Malang', $this->salesA);

        $this->actingAs($this->koor)->get(route('admin.stores.show', $luar))->assertForbidden();
        $this->actingAs($this->koor)->get(route('admin.stores.show', $dalam))->assertOk();
    }

    public function test_store_create_edit_lintas_area_403_dan_assign_ke_area_lain_403(): void
    {
        $luar = $this->store('Kota Kediri', $this->salesB);
        $dalam = $this->store('Kab. Malang', $this->salesA);

        $this->actingAs($this->koor)->get(route('stores.edit', $luar))->assertForbidden();
        $this->actingAs($this->koor)->get(route('stores.edit', $dalam))->assertOk();

        Livewire::actingAs($this->koor)->test(StoreCreate::class)
            ->set('name', 'Toko X')
            ->set('assignedTo', $this->salesB->id)
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('stores', ['name' => 'Toko X']);
    }

    public function test_visit_form_lintas_area_403(): void
    {
        $luar = $this->store('Kota Kediri', $this->salesB);
        $dalam = $this->store('Kab. Malang', $this->salesA);

        $this->actingAs($this->koor)->get(route('visits.create', $luar))->assertForbidden();
        $this->actingAs($this->koor)->get(route('visits.create', $dalam))->assertOk();
        // Sales tetap hanya boleh toko miliknya sendiri.
        $this->actingAs($this->salesA)->get(route('visits.create', $dalam))->assertOk();
        $this->actingAs($this->salesA)->get(route('visits.create', $luar))->assertForbidden();
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `docker compose exec app php artisan test tests/Feature/AreaAccessTest.php`
Expected: FAIL (koordinator melihat/menyentuh semua).

- [ ] **Step 3: `Admin\Stores`**

Tambah helper dan pakai di `edit`, `save`, `delete`, `restore`, `moveStore`, `render`:

```php
    /** Ambil toko dan pastikan pengguna boleh menyentuh areanya. id datang dari client, jadi selalu dicek. */
    private function authorizedStore(int $id, bool $trashed = false): Store
    {
        $store = ($trashed ? Store::onlyTrashed() : Store::query())->findOrFail($id);

        abort_unless(auth()->user()->canAccessArea($store->area), 403);

        return $store;
    }
```

- `edit()`: `$store = $this->authorizedStore($id);`
- `save()`: `$store = $this->authorizedStore((int) $this->editingId);` lalu `$store->update([...])`. Letakkan pemanggilan **sebelum** `$this->validate()`.
- `moveStore()`: `$store = $this->authorizedStore((int) $this->movingId);` dan `$to = User::visibleTo(auth()->user())->where('role', 'sales')->findOrFail($data['moveToId']);`
- `delete()`: `$store = $this->authorizedStore($id);`
- `restore()`: `$store = $this->authorizedStore($id, trashed: true);`
- `render()`: query `Store::query()->visibleTo(auth()->user())->when(...)`; `$moveTargets` jadi `User::visibleTo(auth()->user())->where('role', 'sales')->orderBy('name')->get()`.

- [ ] **Step 4: `Admin\Sales`**

Tambah `public string $area = '';` dan `use App\Support\Areas;`. Ubah:

```php
    public function edit(int $id): void
    {
        $user = User::where('role', 'sales')->findOrFail($id);
        abort_unless(auth()->user()->canAccessArea($user->area), 403);

        $this->editingId = $user->id;
        $this->area = $user->area ?? '';
        // ... (isi lain tetap)
    }

    public function save(): void
    {
        $viewer = auth()->user();

        // Koordinator tanpa area belum bisa mengelola sales siapa pun.
        abort_if(! $viewer->isSuperadmin() && $viewer->area === null, 403);

        if ($this->editingId) {
            abort_unless($viewer->canAccessArea(User::where('role', 'sales')->findOrFail($this->editingId)->area), 403);
        }

        $rules = [ /* rules yang ada, tanpa perubahan */ ];

        if ($viewer->isSuperadmin()) {
            $rules['area'] = ['nullable', Areas::rule()];
        }

        $data = $this->validate($rules);

        // Koordinator: area dipaksa area sendiri; input area dari client diabaikan.
        $data['area'] = $viewer->isSuperadmin() ? ($data['area'] ?: null) : $viewer->area;

        if ($data['password'] === '' || $data['password'] === null) {
            unset($data['password']);
        }

        User::updateOrCreate(['id' => $this->editingId], [...$data, 'role' => 'sales']);
        // ... (flash dan cancel tetap)
    }
```

Tambahkan `'area'` ke daftar `reset(...)` di `cancel()`.

`moveStores()`:

```php
        $viewer = auth()->user();
        $from = User::visibleTo($viewer)->where('role', 'sales')->findOrFail($this->movingFromId);
        $to = User::visibleTo($viewer)->where('role', 'sales')->findOrFail($data['moveToId']);

        // visibleTo pada toko: koordinator tidak bisa menarik toko berarea lain lewat pemindahan massal.
        $count = Store::visibleTo($viewer)->where('created_by', $from->id)
            ->update(['created_by' => $to->id, 'area' => $to->area]);
```

Validasi `moveToId` tetap `['required', 'different:movingFromId', Rule::exists(...)->where('role','sales')]`.

`render()`: `$sales = User::visibleTo(auth()->user())->where('role', 'sales')->orderBy('name')->paginate(20);`, `$storeCounts` dari `Store::visibleTo(auth()->user())->whereNotNull('created_by')...`, `$moveTargets` dari `User::visibleTo(auth()->user())->where('role','sales')->where('id','!=',$this->movingFromId)`.

- [ ] **Step 5: `Admin\StoreShow::mount()`**

```php
        abort_unless(auth()->user()->canAccessArea($store->area), 403);
```

- [ ] **Step 6: `Sales\StoreCreate::mount()` dan `Sales\VisitForm::mount()`**

Ganti `auth()->user()->isAdmin()` di `abort_unless` keduanya menjadi `auth()->user()->canAccessArea($store->area)`:

```php
        abort_unless($store->created_by === auth()->id() || auth()->user()->canAccessArea($store->area), 403);
```

Di `StoreCreate::render()`: `$salesOptions` jadi `auth()->user()->isAdmin() ? User::visibleTo(auth()->user())->where('role', 'sales')->orderBy('name')->get() : collect()`.

- [ ] **Step 7: Jalankan tes**

Run: `docker compose exec app php artisan test tests/Feature/AreaAccessTest.php tests/Feature/AreaCopyTest.php`
Expected: PASS.

- [ ] **Step 8: Commit checkpoint** — `feat: scope stores, sales and visit form to coordinator area`

---

### Task 4: Scope laporan (VisitReport, CSV, Reports, Dashboard, MapView, Performance)

**Files:**
- Modify: `app/Reports/VisitReport.php`, `app/Http/Controllers/ReportCsvController.php`, `app/Livewire/Admin/{Reports,Dashboard,MapView,Performance}.php`
- Test: `tests/Feature/AreaReportsTest.php`

**Interfaces:**
- Consumes: `Store::visibleIds`, `Store::visibleTo`, `User::visibleTo`
- Produces: `VisitReport::query(array $filters, ?User $viewer = null)`, `VisitReport::totals(array $filters, ?User $viewer = null)`; filter opsional `area` di `$filters`.

- [ ] **Step 1: Tulis tes gagal**

`tests/Feature/AreaReportsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\MapView;
use App\Livewire\Admin\Performance;
use App\Models\Store;
use App\Models\User;
use App\Reports\VisitReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaReportsTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    private User $koor;

    private User $salesA;

    private User $salesB;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->koor = $this->user('admin', 'Kab. Malang');
        $this->salesA = $this->user('sales', 'Kab. Malang');
        $this->salesB = $this->user('sales', 'Kota Kediri');
        $this->storeA = $this->store('Kab. Malang', $this->salesA, 'Toko Dalam');
        $this->storeB = $this->store('Kota Kediri', $this->salesB, 'Toko Luar');
        $this->storeA->update(['lat' => -7.9, 'lng' => 112.6]);
        $this->storeB->update(['lat' => -7.8, 'lng' => 112.0]);

        $this->visit($this->storeA, $this->salesA, added: 10);
        $this->visit($this->storeB, $this->salesB, added: 99);
    }

    private function filters(): array
    {
        return ['from' => now()->subDay()->toDateString(), 'to' => now()->toDateString()];
    }

    public function test_visit_report_query_dan_totals_ter_scope(): void
    {
        $this->assertCount(1, VisitReport::query($this->filters(), $this->koor)->get());
        $this->assertSame(10, (int) VisitReport::totals($this->filters(), $this->koor)->added);

        $super = $this->user('superadmin');
        $this->assertSame(109, (int) VisitReport::totals($this->filters(), $super)->added);
        // Tanpa viewer = perilaku lama (dipakai tes lama).
        $this->assertSame(109, (int) VisitReport::totals($this->filters())->added);
    }

    public function test_filter_area_untuk_superadmin(): void
    {
        $totals = VisitReport::totals([...$this->filters(), 'area' => 'Kota Kediri'], $this->user('superadmin'));

        $this->assertSame(99, (int) $totals->added);
    }

    public function test_koordinator_tanpa_area_laporan_kosong(): void
    {
        $nullKoor = $this->user('admin');

        $this->assertCount(0, VisitReport::query($this->filters(), $nullKoor)->get());
        $this->assertSame(0, (int) VisitReport::totals($this->filters(), $nullKoor)->added);
    }

    public function test_kunjungan_toko_terhapus_tetap_terhitung(): void
    {
        $this->storeA->delete();

        $this->assertSame(10, (int) VisitReport::totals($this->filters(), $this->koor)->added);
    }

    public function test_csv_hanya_data_area_koordinator(): void
    {
        $csv = $this->actingAs($this->koor)->get(route('admin.reports.csv'))->streamedContent();

        $this->assertStringContainsString('Toko Dalam', $csv);
        $this->assertStringNotContainsString('Toko Luar', $csv);
    }

    public function test_dashboard_ter_scope(): void
    {
        Livewire::actingAs($this->koor)->test(Dashboard::class)
            ->assertViewHas('totalStores', 1)
            ->assertViewHas('visitCount', 1)
            ->assertViewHas('added', 10);
    }

    public function test_peta_ter_scope(): void
    {
        Livewire::actingAs($this->koor)->test(MapView::class)
            ->assertSee('Toko Dalam')
            ->assertDontSee('Toko Luar');
    }

    public function test_performa_ter_scope(): void
    {
        Livewire::actingAs($this->koor)->test(Performance::class)
            ->assertSee($this->salesA->name)
            ->assertDontSee($this->salesB->name);
    }

    public function test_halaman_report_dropdown_ter_scope(): void
    {
        $this->actingAs($this->koor)->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('Toko Dalam')
            ->assertDontSee('Toko Luar');
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `docker compose exec app php artisan test tests/Feature/AreaReportsTest.php`
Expected: FAIL.

- [ ] **Step 3: `VisitReport`**

Ubah signature dan `base()`:

```php
    public static function query(array $filters, ?User $viewer = null): Builder
    {
        return static::base($filters, $viewer)
        // ... (sisanya tetap)
    }

    public static function totals(array $filters, ?User $viewer = null): object
    {
        return static::base($filters, $viewer)->selectRaw(/* tetap */)->first();
    }

    private static function base(array $filters, ?User $viewer): Builder
    {
        // ... $from / $to tetap
        return VisitItem::query()
            // ... join tetap
            ->whereBetween('visits.visited_at', [$from, $to])
            // Viewer null = tanpa pembatasan (tes lama); koordinator dibatasi ke tokonya sendiri.
            ->when($viewer, fn ($q) => $q->whereIn('visits.store_id', Store::visibleIds($viewer)))
            ->when($filters['area'] ?? null, fn ($q, $area) => $q->where('stores.area', $area))
            // ... when user_id / product_id / store_id tetap
    }
```

Tambah `use App\Models\Store; use App\Models\User;` dan tambah `area?:string` di docblock `@param`.

- [ ] **Step 4: `ReportCsvController`**

```php
        $filters = $request->only(['from', 'to', 'user_id', 'product_id', 'store_id', 'area']);
        $viewer = $request->user();
```
`use ($filters, $viewer)` pada closure, dan `VisitReport::query($filters, $viewer)`.

- [ ] **Step 5: `Admin\Reports`**

Tambah `#[Url] public string $area = '';`, tambahkan `'area' => $this->area` ke `filters()`. `render()`:

```php
        $viewer = auth()->user();

        return view('livewire.admin.reports', [
            'rows' => VisitReport::query($this->filters(), $viewer)->paginate(30),
            'totals' => VisitReport::totals($this->filters(), $viewer),
            'salesUsers' => User::visibleTo($viewer)->orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'stores' => Store::visibleTo($viewer)->orderBy('name')->get(['id', 'name']),
        ]);
```

Filter `area` dari URL hanya berlaku efektif untuk superadmin; untuk koordinator ia beririsan dengan `visibleIds` sehingga tidak bisa melebar.

- [ ] **Step 6: `Admin\Dashboard`**

Di awal `render()`: `$viewer = auth()->user(); $visible = Store::visibleIds($viewer);`. Terapkan:
- `$monthly` dan `$topProducts`: tambah `->whereIn('visits.store_id', $visible)`.
- `$stale`: `Store::query()->visibleTo($viewer)->where('active', true)`.
- `$storesBySales`: `Store::query()->visibleTo($viewer)->whereNotNull('created_by')`.
- `'totalStores' => Store::visibleTo($viewer)->where('active', true)->count()`, `'newStores' => Store::visibleTo($viewer)->where('created_at', '>=', $monthStart)->count()`, `'visitCount' => Visit::whereIn('store_id', $visible)->where('visited_at', '>=', $monthStart)->count()`.

- [ ] **Step 7: `Admin\MapView`**

Tambah `#[Url] public string $area = '';` (`use Livewire\Attributes\Url;`). Di `render()`:

```php
        $viewer = auth()->user();
        // Filter area hanya bermakna untuk superadmin; koordinator sudah terbatas oleh visibleTo.
        $inArea = fn ($q) => $q->when($viewer->isSuperadmin() && $this->area !== '', fn ($q) => $q->where('stores.area', $this->area));
```
Markers: `Store::query()->visibleTo($viewer)->tap($inArea)->where('active', true)...`; `missingCoords`: `Store::visibleTo($viewer)->tap($inArea)->where('active', true)->whereNull('lat')->count()`.

- [ ] **Step 8: `Admin\Performance`**

Tambah `#[Url] public string $area = '';`. Di `render()`:

```php
        $viewer = auth()->user();
        $area = $viewer->isSuperadmin() ? $this->area : '';

        $visitStats = Visit::query()
            ->join('stores', 'stores.id', '=', 'visits.store_id')
            ->leftJoin('visit_items', 'visit_items.visit_id', '=', 'visits.id')
            ->whereBetween('visits.visited_at', $range)
            ->whereIn('visits.store_id', Store::visibleIds($viewer))
            ->when($area !== '', fn ($q) => $q->where('stores.area', $area))
            ->groupBy('visits.user_id')
            // ->get([...]) tetap
```
`$newStores`: `Store::visibleTo($viewer)->when($area !== '', fn ($q) => $q->where('area', $area))->whereBetween(...)`. `$rows`: `User::visibleTo($viewer)->when($area !== '', fn ($q) => $q->where('users.area', $area))->orderBy('name')`.

- [ ] **Step 9: Jalankan tes**

Run: `docker compose exec app php artisan test tests/Feature/AreaReportsTest.php tests/Feature/VisitReportTest.php`
Expected: PASS (VisitReportTest lama tetap hijau: viewer default null).

- [ ] **Step 10: Commit checkpoint** — `feat: scope reports, dashboard, map and performance by area`

---

### Task 5: Gate route dan nav

**Files:**
- Modify: `routes/web.php`, `resources/views/components/layout.blade.php`, `tests/Feature/AdminSoftDeleteTest.php`
- Test: `tests/Feature/AreaRoutesTest.php`

- [ ] **Step 1: Tulis tes gagal**

`tests/Feature/AreaRoutesTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaRoutesTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    public function test_koordinator_403_di_produk_dan_pengguna(): void
    {
        $koor = $this->user('admin', 'Kab. Malang');

        $this->actingAs($koor)->get(route('admin.products'))->assertForbidden();
        $this->actingAs($koor)->get(route('admin.users'))->assertForbidden();
    }

    public function test_superadmin_boleh_produk_dan_pengguna(): void
    {
        $super = $this->user('superadmin');

        $this->actingAs($super)->get(route('admin.products'))->assertOk();
        $this->actingAs($super)->get(route('admin.users'))->assertOk();
    }

    public function test_nav_koordinator_tanpa_produk_pengguna_area(): void
    {
        $html = $this->actingAs($this->user('admin', 'Kab. Malang'))->get(route('admin.dashboard'))->getContent();

        $this->assertStringNotContainsString(route('admin.products'), $html);
        $this->assertStringNotContainsString(route('admin.users'), $html);
        $this->assertStringNotContainsString(route('admin.areas'), $html);
    }

    public function test_nav_superadmin_memuat_produk_dan_area(): void
    {
        $html = $this->actingAs($this->user('superadmin'))->get(route('admin.dashboard'))->getContent();

        $this->assertStringContainsString(route('admin.products'), $html);
        $this->assertStringContainsString(route('admin.areas'), $html);
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `docker compose exec app php artisan test tests/Feature/AreaRoutesTest.php`
Expected: FAIL (`Route [admin.areas] not defined` dan koordinator masih 200).

- [ ] **Step 3: Routes**

Di `routes/web.php`: hapus baris `produk` dan `pengguna` dari grup `can:admin`, tambah grup baru setelahnya, dan `use App\Livewire\Admin\Areas;`:

```php
// Master data dan manajemen akun: hanya superadmin. Koordinator Area tidak masuk sini.
Route::middleware(['auth', 'can:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('produk', Products::class)->name('products');
    Route::get('pengguna', Users::class)->name('users');
    Route::get('area', Areas::class)->name('areas');
});
```

`Areas` komponen dibuat di Task 7; agar route tidak patah di antara task, buat stub minimal sekarang:

`app/Livewire/Admin/Areas.php`:
```php
<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('Rollup Area')]
class Areas extends Component
{
    public function render()
    {
        return view('livewire.admin.areas', ['rows' => collect()]);
    }
}
```
dan `resources/views/livewire/admin/areas.blade.php` berisi `<div><h1 class="text-2xl font-bold tracking-tight">Rollup Area</h1></div>` (diisi penuh di Task 7).

- [ ] **Step 4: Nav**

Di `layout.blade.php`: tambah `'admin.areas' => 'Area',` setelah `'admin.sales' => 'Sales',`, dan ganti baris `@continue(...)` dengan:

```blade
            @continue(in_array($route, ['admin.users', 'admin.products', 'admin.areas'], true) && ! $user->isSuperadmin())
```

- [ ] **Step 5: Perbaiki tes lama**

Di `tests/Feature/AdminSoftDeleteTest.php` `setUp()`: ubah `'role' => 'admin'` pada `$this->admin` menjadi `'role' => 'superadmin'` (Produk kini superadmin-only dan koordinator tanpa area tidak melihat toko).

- [ ] **Step 6: Jalankan seluruh tes**

Run: `docker compose exec app php artisan test`
Expected: PASS semua.

- [ ] **Step 7: Commit checkpoint** — `feat: restrict products, users and area rollup to superadmin`

---

### Task 6: UI (label role, input area, kolom, filter)

**Files:**
- Create: `resources/views/components/area-input.blade.php`, `resources/views/components/area-filter.blade.php`
- Modify: `app/Livewire/Admin/Users.php`; `resources/views/livewire/admin/{users,sales,stores,performance,reports,map-view}.blade.php`; `app/Livewire/Admin/{Stores,Sales}.php` (filter)
- Test: `tests/Feature/AreaUiTest.php`

**Interfaces:**
- Consumes: `Areas::all()`, `Areas::rule()`, `User::roleLabel()`, `User::areaSuffix()`
- Produces: komponen blade `<x-area-input model="area" />` dan `<x-area-filter />` (memakai `wire:model.live="area"`).

- [ ] **Step 1: Tulis tes gagal**

`tests/Feature/AreaUiTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Livewire\Admin\Sales as AdminSales;
use App\Livewire\Admin\Stores;
use App\Livewire\Admin\Users;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaUiTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    public function test_users_simpan_area_valid_dan_tolak_di_luar_daftar(): void
    {
        $super = $this->user('superadmin');

        Livewire::actingAs($super)->test(Users::class)
            ->set('name', 'Koor')->set('email', 'koor@test.local')->set('role', 'admin')
            ->set('password', 'password123')->set('area', 'Atlantis')
            ->call('save')
            ->assertHasErrors('area');

        Livewire::actingAs($super)->test(Users::class)
            ->set('name', 'Koor')->set('email', 'koor@test.local')->set('role', 'admin')
            ->set('password', 'password123')->set('area', 'Kab. Malang')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'koor@test.local', 'area' => 'Kab. Malang']);
    }

    public function test_users_area_kosong_disimpan_null(): void
    {
        Livewire::actingAs($this->user('superadmin'))->test(Users::class)
            ->set('name', 'X')->set('email', 'x@test.local')->set('role', 'sales')
            ->set('password', 'password123')->set('area', '')
            ->call('save');

        $this->assertDatabaseHas('users', ['email' => 'x@test.local', 'area' => null]);
    }

    public function test_superadmin_sales_area_di_luar_daftar_ditolak(): void
    {
        Livewire::actingAs($this->user('superadmin'))->test(AdminSales::class)
            ->set('name', 'S')->set('email', 's@test.local')->set('password', 'password123')
            ->set('area', 'Atlantis')
            ->call('save')
            ->assertHasErrors('area');
    }

    public function test_label_role_dan_area_tampil_di_pengguna(): void
    {
        $this->user('admin', 'Kab. Malang');

        Livewire::actingAs($this->user('superadmin'))->test(Users::class)
            ->assertSee('Koordinator Area')
            ->assertSee('Kab. Malang');
    }

    public function test_toko_tanpa_area_dan_judul_koordinator(): void
    {
        $this->store(null, null, 'Toko Yatim');
        $koor = $this->user('admin', 'Kab. Malang');
        $this->store('Kab. Malang', null, 'Toko Ada');

        Livewire::actingAs($this->user('superadmin'))->test(Stores::class)
            ->assertSee('Tanpa area');

        Livewire::actingAs($koor)->test(Stores::class)
            ->assertSee('Toko · Kab. Malang');
    }

    public function test_filter_area_hanya_untuk_superadmin(): void
    {
        $a = $this->store('Kab. Malang', null, 'Toko Malang');
        $b = $this->store('Kota Kediri', null, 'Toko Kediri');

        Livewire::actingAs($this->user('superadmin'))->test(Stores::class)
            ->set('area', 'Kota Kediri')
            ->assertSee('Toko Kediri')
            ->assertDontSee('Toko Malang');

        // Koordinator: nilai area dari client tidak melebarkan hasil.
        Livewire::actingAs($this->user('admin', 'Kab. Malang'))->test(Stores::class)
            ->set('area', 'Kota Kediri')
            ->assertSee('Toko Malang')
            ->assertDontSee('Toko Kediri');
    }

    public function test_peta_filter_area_lewat_url(): void
    {
        $this->store('Kab. Malang', null, 'Toko Malang')->update(['lat' => -7.9, 'lng' => 112.6]);
        $this->store('Kota Kediri', null, 'Toko Kediri')->update(['lat' => -7.8, 'lng' => 112.0]);

        $this->actingAs($this->user('superadmin'))->get(route('admin.map', ['area' => 'Kota Kediri']))
            ->assertSee('Toko Kediri')
            ->assertDontSee('Toko Malang');
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `docker compose exec app php artisan test tests/Feature/AreaUiTest.php`
Expected: FAIL.

- [ ] **Step 3: Komponen blade**

`resources/views/components/area-input.blade.php`:
```blade
@props(['model' => 'area'])

{{-- Datalist native: bisa dicari sambil mengetik. Server tetap memvalidasi nilainya (Areas::rule()). --}}
<input wire:model="{{ $model }}" list="daftar-area" type="text" placeholder="Pilih / ketik area" autocomplete="off"
       class="isian isian-kecil">
<datalist id="daftar-area">
    @foreach (\App\Support\Areas::all() as $area)
        <option value="{{ $area }}"></option>
    @endforeach
</datalist>
```

`resources/views/components/area-filter.blade.php`:
```blade
@props(['model' => 'area'])

@if (auth()->user()->isSuperadmin())
    <div>
        <label class="block label-kecil mb-1">Area</label>
        <select wire:model.live="{{ $model }}" class="isian isian-kecil w-auto">
            <option value="">Semua area</option>
            @foreach (\App\Support\Areas::all() as $area)
                <option value="{{ $area }}">{{ $area }}</option>
            @endforeach
        </select>
    </div>
@endif
```

- [ ] **Step 4: `Admin\Users`**

Tambah `public string $area = '';`, `use App\Support\Areas;`. `edit()`: `$this->area = $user->area ?? '';`. `cancel()`: tambah `'area'` ke `reset`. `save()`: tambah rule `'area' => ['nullable', Areas::rule()],` dan setelah validate: `$data['area'] = $data['area'] ?: null;`.

`users.blade.php`: tambah `lg:grid-cols-6` → `lg:grid-cols-7`, blok field (setelah Role):
```blade
        <div>
            <label class="block label-kecil mb-1">Area</label>
            <x-area-input model="area" />
            @error('area') <p class="mt-1 text-xs text-bata">{{ $message }}</p> @enderror
        </div>
```
`lg:col-span-6` pada label Aktif → `lg:col-span-7`. Opsi `Admin` → `Koordinator Area`. Header tabel: tambah `<th class="text-left font-normal px-4 py-3">Area</th>` setelah Role; sel Role `{{ ucfirst($user->role) }}` → `{{ $user->roleLabel() }}`; tambah sel `<td class="px-4 py-2.5">{!! $user->area ?? '<span class="text-tinta/50">Tanpa area</span>' !!}</td>` — **jangan** pakai `{!! !!}` untuk `$user->area`; tulis:
```blade
<td class="px-4 py-2.5">
    @if ($user->area) {{ $user->area }} @else <span class="text-tinta/50">Tanpa area</span> @endif
</td>
```

- [ ] **Step 5: `Admin\Stores` filter + tampilan**

`Stores.php`: `#[Url(as: 'area', except: '')] public string $area = '';` dengan `updatedArea()` yang memanggil `$this->resetPage()`. Di `render()` tambahkan pada query: `->when(auth()->user()->isSuperadmin() && $this->area !== '', fn ($q) => $q->where('stores.area', $this->area))`.

`stores.blade.php`: `<h1>Toko{{ auth()->user()->areaSuffix() }}</h1>`; di blok header kanan sebelum input cari tambah `<x-area-filter />`; `<th>Area</th>` setelah "Pemilik"; sel:
```blade
<td class="px-4 py-2.5 text-tinta/70">
    @if ($store->area) {{ $store->area }} @else <span class="text-tinta/50">Tanpa area</span> @endif
</td>
```
Kedua `colspan="7"` → `colspan="8"`.

- [ ] **Step 6: `Admin\Sales` filter + tampilan**

`Sales.php`: `#[Url(as: 'filter_area', except: '')] public string $filterArea = '';` (nama beda dari `$area` form!) + `updatedFilterArea()` → `resetPage()`. Di `render()`, `$sales` query tambah `->when(auth()->user()->isSuperadmin() && $this->filterArea !== '', fn ($q) => $q->where('users.area', $this->filterArea))`.

`sales.blade.php`: `<h1>Sales{{ auth()->user()->areaSuffix() }}</h1>`; filter: `<x-area-filter model="filterArea" />` di bawah `<h1>`; form: `lg:grid-cols-5` → `lg:grid-cols-6` dan `lg:col-span-5` → `lg:col-span-6`; field area untuk superadmin (sebelum tombol) dan teks area untuk koordinator:
```blade
        @if (auth()->user()->isSuperadmin())
            <div>
                <label class="block label-kecil mb-1">Area</label>
                <x-area-input model="area" />
                @error('area') <p class="mt-1 text-xs text-bata">{{ $message }}</p> @enderror
            </div>
        @elseif (auth()->user()->area)
            <p class="text-sm text-tinta/70">Area: <strong>{{ auth()->user()->area }}</strong></p>
        @else
            <p class="text-sm text-bata">Area kamu belum diatur, hubungi superadmin.</p>
        @endif
```
Tombol simpan: `<button @disabled(! auth()->user()->isSuperadmin() && ! auth()->user()->area) class="tombol tombol-utama flex-1">`. Tabel: `<th>Area</th>` setelah HP, sel area seperti di Stores, semua `colspan="6"` → `colspan="7"`.

- [ ] **Step 7: Performance, Reports, Peta**

- `performance.blade.php` dan `reports.blade.php`: tambah `<x-area-filter />` di dalam kartu filter (setelah "Sampai"); `reports.blade.php` grid `lg:grid-cols-5` → `lg:grid-cols-6`. Judul: `Performa Sales{{ auth()->user()->areaSuffix() }}` dan `Report{{ auth()->user()->areaSuffix() }}`. Label role di `performance.blade.php`: `({{ $row['user']->role }})` → `({{ $row['user']->roleLabel() }})`.
- `map-view.blade.php`: judul `Peta Toko{{ auth()->user()->areaSuffix() }}`; di baris header tambah untuk superadmin, form GET biasa (marker tidak di-render ulang oleh Livewire):
```blade
        @if (auth()->user()->isSuperadmin())
            <form method="GET" class="flex items-center gap-2">
                <select name="area" onchange="this.form.submit()" class="isian isian-kecil w-auto">
                    <option value="">Semua area</option>
                    @foreach (\App\Support\Areas::all() as $a)
                        <option value="{{ $a }}" @selected($area === $a)>{{ $a }}</option>
                    @endforeach
                </select>
            </form>
        @endif
```

- [ ] **Step 8: Jalankan tes**

Run: `docker compose exec app php artisan test tests/Feature/AreaUiTest.php`
Expected: PASS.

- [ ] **Step 9: Commit checkpoint** — `feat: area inputs, role labels, area columns and filters`

---

### Task 7: Rollup `admin/area`

**Files:**
- Create: `app/Reports/AreaRollup.php`
- Modify: `app/Livewire/Admin/Areas.php` (stub Task 5), `resources/views/livewire/admin/areas.blade.php`
- Test: `tests/Feature/AreaRollupTest.php`

**Interfaces:**
- Consumes: `BuildsAreaFixtures`
- Produces: `AreaRollup::rows(array{0:Carbon,1:Carbon} $range): Collection` berisi elemen `['area' => ?string, 'koordinators' => Collection<User>, 'stores' => int, 'visits' => int, 'sold' => int, 'remaining' => int, 'sales' => Collection<array{user:User,stores:int,visits:int,sold:int,remaining:int}>]`

- [ ] **Step 1: Tulis tes gagal**

`tests/Feature/AreaRollupTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Reports\AreaRollup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaRollupTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    private function range(): array
    {
        return [now()->startOfMonth(), now()->endOfDay()];
    }

    public function test_angka_per_area_koordinator_dan_sales(): void
    {
        $koor = $this->user('admin', 'Kab. Malang');
        $sales = $this->user('sales', 'Kab. Malang');
        $storeA = $this->store('Kab. Malang', $sales);
        // Dua kunjungan (bisa dalam detik yang sama): sisa akhir harus 18, bukan 10 + 18.
        $this->visit($storeA, $sales, found: 0, added: 20);
        $this->visit($storeA, $sales, found: 8, added: 10);

        $other = $this->user('sales', 'Kota Kediri');
        $this->visit($this->store('Kota Kediri', $other), $other, added: 5);

        $rows = AreaRollup::rows($this->range())->keyBy('area');

        $malang = $rows['Kab. Malang'];
        $this->assertSame([$koor->id], $malang['koordinators']->pluck('id')->all());
        $this->assertSame(1, $malang['stores']);
        $this->assertSame(2, $malang['visits']);
        $this->assertSame(12, $malang['sold']);
        $this->assertSame(18, $malang['remaining']);

        $row = $malang['sales']->first();
        $this->assertSame($sales->id, $row['user']->id);
        $this->assertSame([1, 2, 12, 18], [$row['stores'], $row['visits'], $row['sold'], $row['remaining']]);

        $kediri = $rows['Kota Kediri'];
        $this->assertSame([1, 1, 0, 5], [$kediri['stores'], $kediri['visits'], $kediri['sold'], $kediri['remaining']]);
    }

    public function test_area_null_muncul_di_akhir(): void
    {
        $this->store(null);
        $this->store('Kab. Malang');

        $areas = AreaRollup::rows($this->range())->pluck('area')->all();

        $this->assertSame(['Kab. Malang', null], $areas);
    }

    public function test_halaman_hanya_superadmin(): void
    {
        $this->actingAs($this->user('admin', 'Kab. Malang'))->get(route('admin.areas'))->assertForbidden();

        $this->actingAs($this->user('superadmin'))->get(route('admin.areas'))
            ->assertOk()
            ->assertSee('Rollup Area');
    }

    public function test_halaman_menampilkan_area_dan_tanpa_area(): void
    {
        $this->store('Kab. Malang');
        $this->store(null);

        $this->actingAs($this->user('superadmin'))->get(route('admin.areas'))
            ->assertSee('Kab. Malang')
            ->assertSee('Tanpa area');
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `docker compose exec app php artisan test tests/Feature/AreaRollupTest.php`
Expected: FAIL (`Class "App\Reports\AreaRollup" not found`).

- [ ] **Step 3: `AreaRollup`**

`app/Reports/AreaRollup.php`:

```php
<?php

namespace App\Reports;

use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rollup superadmin: area -> koordinator -> sales. Tiga query agregat kecil
 * digabung di PHP; semua kolom select ada di GROUP BY (ONLY_FULL_GROUP_BY).
 */
class AreaRollup
{
    /** @param array{0: \Carbon\Carbon, 1: \Carbon\Carbon} $range */
    public static function rows(array $range): Collection
    {
        // Kunjungan dikelompokkan per area toko dan per sales pencatat.
        $visits = Visit::query()
            ->join('stores', 'stores.id', '=', 'visits.store_id')
            ->leftJoin('visit_items', 'visit_items.visit_id', '=', 'visits.id')
            ->whereBetween('visits.visited_at', $range)
            ->groupBy('stores.area', 'visits.user_id')
            ->get([
                'stores.area', 'visits.user_id',
                DB::raw('COUNT(DISTINCT visits.id) as visit_count'),
                DB::raw('COALESCE(SUM(visit_items.qty_sold), 0) as sold'),
            ]);

        $stores = Store::query()
            ->groupBy('area', 'created_by')
            ->get(['area', 'created_by', DB::raw('COUNT(*) as total')]);

        // Stok saat ini = qty_left dari kunjungan TERAKHIR tiap toko. Urut visited_at lalu id
        // supaya dua kunjungan dengan visited_at kembar tidak terhitung dua kali.
        // Alias `remaining`, bukan `left` (reserved word MySQL).
        $stock = DB::table('visit_items')
            ->join('visits', 'visits.id', '=', 'visit_items.visit_id')
            ->join('stores', 'stores.id', '=', 'visits.store_id')
            ->whereNull('stores.deleted_at')
            ->whereRaw('visits.id = (
                select v2.id from visits v2 where v2.store_id = visits.store_id
                order by v2.visited_at desc, v2.id desc limit 1)')
            ->groupBy('stores.area', 'stores.created_by')
            ->get(['stores.area', 'stores.created_by', DB::raw('SUM(visit_items.qty_left) as remaining')]);

        $users = User::whereIn('role', ['admin', 'sales'])->orderBy('name')->get();

        return $users->pluck('area')
            ->merge($stores->pluck('area'))->merge($visits->pluck('area'))
            ->unique()
            // Area null (tanpa area) selalu di akhir.
            ->sortBy(fn (?string $area) => $area === null ? "\u{10FFFF}" : $area)
            ->values()
            ->map(function (?string $area) use ($users, $visits, $stores, $stock) {
                $v = $visits->where('area', $area);
                $s = $stores->where('area', $area);
                $k = $stock->where('area', $area);

                return [
                    'area' => $area,
                    'koordinators' => $users->where('role', 'admin')->where('area', $area)->values(),
                    'stores' => (int) $s->sum('total'),
                    'visits' => (int) $v->sum('visit_count'),
                    'sold' => (int) $v->sum('sold'),
                    'remaining' => (int) $k->sum('remaining'),
                    'sales' => $users->where('role', 'sales')->where('area', $area)->values()
                        ->map(fn (User $u) => [
                            'user' => $u,
                            'stores' => (int) $s->where('created_by', $u->id)->sum('total'),
                            'visits' => (int) $v->where('user_id', $u->id)->sum('visit_count'),
                            'sold' => (int) $v->where('user_id', $u->id)->sum('sold'),
                            'remaining' => (int) $k->where('created_by', $u->id)->sum('remaining'),
                        ]),
                ];
            });
    }
}
```

- [ ] **Step 4: Komponen dan view**

`app/Livewire/Admin/Areas.php` (ganti stub):

```php
<?php

namespace App\Livewire\Admin;

use App\Reports\AreaRollup;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('Rollup Area')]
class Areas extends Component
{
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    public function render()
    {
        return view('livewire.admin.areas', [
            'rows' => AreaRollup::rows([
                Carbon::parse($this->from)->startOfDay(),
                Carbon::parse($this->to)->endOfDay(),
            ]),
        ]);
    }
}
```

`resources/views/livewire/admin/areas.blade.php`:

```blade
<div class="space-y-4">
    <h1 class="text-2xl font-bold tracking-tight">Rollup Area</h1>

    <div class="kartu flex flex-wrap gap-3 items-end">
        <div>
            <label class="block label-kecil mb-1">Dari</label>
            <input wire:model.live="from" type="date" class="isian isian-kecil w-auto">
        </div>
        <div>
            <label class="block label-kecil mb-1">Sampai</label>
            <input wire:model.live="to" type="date" class="isian isian-kecil w-auto">
        </div>
        <p class="label-kecil">Kunjungan dan terjual mengikuti rentang tanggal. Sisa stok = kondisi saat ini.</p>
    </div>

    @forelse ($rows as $row)
        <details class="kartu !p-0" wire:key="area-{{ $row['area'] ?? 'none' }}">
            <summary class="cursor-pointer px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                <span class="font-display font-bold">{{ $row['area'] ?? 'Tanpa area' }}</span>
                <span class="text-sm tabular-nums text-tinta/70">
                    {{ $row['stores'] }} toko · {{ $row['visits'] }} kunjungan ·
                    <span class="text-daun font-semibold">{{ number_format($row['sold']) }} terjual</span> ·
                    {{ number_format($row['remaining']) }} tersisa
                </span>
            </summary>

            <div class="px-4 pb-4 space-y-2 border-t border-tinta/10">
                <p class="pt-3 label-kecil">
                    Koordinator:
                    {{ $row['koordinators']->pluck('name')->join(', ') ?: '—' }}
                </p>

                <table class="w-full text-sm min-w-[520px]">
                    <thead class="text-xs text-tinta/70 border-b border-tinta/10">
                        <tr>
                            <th class="text-left font-normal py-2">Sales</th>
                            <th class="text-right font-normal py-2">Toko</th>
                            <th class="text-right font-normal py-2">Kunjungan</th>
                            <th class="text-right font-normal py-2">Terjual</th>
                            <th class="text-right font-normal py-2">Tersisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-tinta/8 tabular-nums">
                        @forelse ($row['sales'] as $s)
                            <tr>
                                <td class="py-2 font-medium">{{ $s['user']->name }}</td>
                                <td class="py-2 text-right">{{ $s['stores'] }}</td>
                                <td class="py-2 text-right">{{ $s['visits'] }}</td>
                                <td class="py-2 text-right text-daun font-semibold">{{ number_format($s['sold']) }}</td>
                                <td class="py-2 text-right">{{ number_format($s['remaining']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-tinta/70">Belum ada sales di area ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>
    @empty
        <div class="kartu text-center text-tinta/70">Belum ada data area.</div>
    @endforelse
</div>
```

- [ ] **Step 5: Jalankan tes**

Run: `docker compose exec app php artisan test tests/Feature/AreaRollupTest.php`
Expected: PASS.

- [ ] **Step 6: Commit checkpoint** — `feat: add superadmin area rollup page`

---

### Task 8: Penutup (pint, aset, dokumentasi, suite penuh)

**Files:**
- Modify: `CLAUDE.md`, `public/build/*` (hasil build)

- [ ] **Step 1: Pint**

Run: `docker compose exec app vendor/bin/pint`
Expected: file yang diubah diformat, tanpa error.

- [ ] **Step 2: Build aset** (class Tailwind baru seperti `text-tinta/50` harus masuk `public/build`)

Run: `docker compose run --rm vite npm run build`
Expected: build sukses; `git status` menampilkan perubahan di `public/build`.

- [ ] **Step 3: Update `CLAUDE.md`**

Di bagian "Roles and authorization" tambahkan paragraf:

```markdown
**Area.** `users.area` dan `stores.area` (string nullable) diisi dari daftar hardcode `App\Support\Areas` (38 kab/kota Jawa Timur, berawalan `Kab.`/`Kota`). Role `admin` dilabeli "Koordinator Area" di UI (`User::roleLabel()`), nilai DB tidak berubah. Koordinator hanya melihat dan mengelola data di areanya: list lewat `Store::visibleTo($user)` / `User::visibleTo($user)`, aksi per-record lewat `User::canAccessArea($area)` (inline `abort_unless`, sama seperti cek kepemilikan lain). Koordinator tanpa area tidak melihat apa pun. `stores.area` disalin dari sales pemilik saat toko dibuat atau dipindah; mengubah area sales tidak menggeser toko lamanya. Produk, Pengguna, dan Rollup Area (`admin/area`) khusus superadmin.
```

- [ ] **Step 4: Suite penuh**

Run: `docker compose exec app php artisan test`
Expected: semua PASS (termasuk `RecordVisitTest`, `VisitReportTest`, `AdminSoftDeleteTest` yang diperbarui).

- [ ] **Step 5: Cek manual singkat** (`docker compose up -d`, http://localhost:8000)

1. Login `super@ministo.test`, buka Pengguna, beri `admin@ministo.test` area "Kab. Malang" dan `budi@ministo.test` area "Kab. Malang", `sari@ministo.test` area "Kota Kediri".
2. Login `admin@ministo.test`: nav tanpa Produk/Pengguna/Area; hanya melihat toko/sales Kab. Malang; `/admin/produk` dan `/admin/pengguna` 403.
3. Login superadmin: `/admin/area` menampilkan rollup, filter area bekerja di Toko dan Peta.

- [ ] **Step 6: Commit checkpoint** — `chore: format, rebuild assets and document area`

---

## Self-Review

**Spec coverage:** skema/migration/Areas/model → T1; aturan salin area (create, assignedTo, reassign, area sales berubah) → T2; scope dan 403 per halaman (Toko, Sales, StoreShow, StoreCreate, VisitForm) → T3; Dashboard/Peta/Performa/Report/CSV → T4; produk dan pengguna superadmin-only, nav → T5; label role, input datalist, kolom, filter, judul area, pesan koordinator tanpa area → T6; rollup → T7; pint, build, doc → T8. Semua item "Test yang wajib ada" di spec punya tes (area di luar daftar: T6; koordinator 403 produk/pengguna: T5; rollup 403: T7).

**Penyimpangan kecil dari spec (perlu diketahui):** (1) filter area Peta memakai form GET (reload penuh), bukan filter live, karena marker dirender sekali di `wire:ignore`. (2) Untuk id lintas area di pemindahan (`moveToId`), hasilnya 404 (`findOrFail` pada `visibleTo`), bukan 403; semua aksi terhadap record yang dimiliki area lain tetap 403. (3) Dashboard tidak mendapat filter area (spec tidak memintanya, hanya scope).

**Type consistency:** `VisitReport::query/totals(array, ?User)`, `Store::visibleIds(User): Builder`, `User::canAccessArea(?string)`, `AreaRollup::rows(array)` dipakai konsisten di semua task. Properti `Sales::$area` (form) dan `Sales::$filterArea` (filter) sengaja dibedakan.
