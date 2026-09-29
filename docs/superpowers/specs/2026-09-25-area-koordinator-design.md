# Desain: Area dan Koordinator Area

Tanggal: 2026-09-25 · Status: menunggu review

## Tujuan

Tambah `area` pada `User` dan `Store`. Role `admin` dilabeli "Koordinator Area" (nilai DB tetap `admin`). Koordinator hanya melihat dan mengelola data areanya sendiri. Superadmin melihat semua, plus rollup per area, per koordinator, dan per sales.

## Keputusan yang sudah disepakati

- Produk: master data bersama, **superadmin-only**. Koordinator kehilangan akses `admin/produk`.
- Area: daftar **hardcode** 38 kabupaten/kota Jawa Timur, bukan tabel DB.
- Input area: `<input list>` + `<datalist>` native, tanpa library JS. Server tetap validasi `Rule::in`.
- Koordinator ↔ Sales: **implisit** lewat `area` yang sama. Tanpa FK atau tabel relasi.
- `stores.area`: **kolom sendiri** (disalin, bukan diturunkan lewat join).
- `role` di DB tidak berubah. Hanya label UI.
- User tanpa area: nullable, tanpa default.
- Koordinator boleh kelola sales di areanya sendiri.
- Halaman `Users`: superadmin-only.
- Rollup: halaman baru `admin/area` (superadmin-only).
- Pendekatan scoping: **scope eksplisit** (`visibleTo`) di tiap halaman, bukan global scope.

## 1. Skema dan data

Migration baru:
- `users.area` string nullable + index.
- `stores.area` string nullable + index.
- Backfill `stores.area` dari `users.area` pembuatnya. Awalnya semua null karena user seed belum punya area.

`App\Support\Areas`: konstanta 38 nilai dengan method `all()` dan `rule()`.
- Kabupaten (29): Bangkalan, Banyuwangi, Blitar, Bojonegoro, Bondowoso, Gresik, Jember, Jombang, Kediri, Lamongan, Lumajang, Madiun, Magetan, Malang, Mojokerto, Nganjuk, Ngawi, Pacitan, Pamekasan, Pasuruan, Ponorogo, Probolinggo, Sampang, Sidoarjo, Situbondo, Sumenep, Trenggalek, Tuban, Tulungagung. Disimpan dengan awalan `Kab.`
- Kota (9): Batu, Blitar, Kediri, Madiun, Malang, Mojokerto, Pasuruan, Probolinggo, Surabaya. Disimpan dengan awalan `Kota`.

Model:
- `area` masuk `$fillable` di `User` dan `Store`.
- `User::roleLabel()`: `admin` → "Koordinator Area", `sales` → "Sales", `superadmin` → "Superadmin".
- `User::isKoordinator()`: `role === 'admin'`. `isAdmin()` tidak berubah.

Aturan penyalinan area ke toko:
- Toko dibuat: `stores.area` = area sales pembuat. Kalau admin membuat toko lewat `assignedTo`, area = area sales yang dipilih.
- Toko di-reassign ke sales lain: `stores.area` ikut area sales baru.
- Mengubah area sales tidak mengubah area toko lamanya (riwayat per area stabil).

## 2. Otorisasi dan scope

- `User::canAccessArea(?string $area)`: superadmin true; koordinator true hanya jika `$area !== null && $area === $this->area`; sales false.
- `Store::scopeVisibleTo($viewer)` dan `User::scopeVisibleTo($viewer)`: superadmin tanpa filter; koordinator `where('area', $viewer->area)` (untuk `User` ditambah `role = 'sales'`).
- **Gotcha:** `where('area', null)` di Laravel menjadi `IS NULL`. Koordinator tanpa area harus mendapat hasil kosong (`whereRaw('1 = 0')`), bukan data berarea null.
- `VisitReport::base()` sudah join ke `stores`. Tambah parameter viewer dan `where stores.area`. Dipakai bersama halaman Report dan `ReportCsvController`.
- Route `admin/produk`: `can:superadmin`. Menu Produk disembunyikan untuk koordinator. Route `admin/*` lain tetap `can:admin`.
- Pengecekan per-record memakai pola inline `abort_unless(...)`, karena id di aksi Livewire datang dari client.

| Halaman | Perubahan |
|---|---|
| Dashboard, Peta, Performa, Report | Query di-scope `visibleTo`. |
| Toko | List di-scope. Delete, restore, reassign dicek per-record. Target reassign hanya sales di area yang sama. |
| Sales | List di-scope. Koordinator membuat sales dengan area dipaksa sama dengan areanya. Edit dan nonaktifkan dicek per-record. Tidak bisa mengubah area sales ke area lain. Koordinator tanpa area tidak bisa membuat sales. |
| `StoreShow`, `StoreCreate` (edit) | `abort_unless(canAccessArea($store->area) atau pembuat sendiri)`. Dropdown `assignedTo` hanya berisi sales yang visible. |
| `VisitForm` | Diperketat: admin harus `canAccessArea($store->area)`. Sebelumnya semua admin boleh mencatat kunjungan di toko mana pun. |
| `Users` | Superadmin-only. |

## 3. UI

Semua memakai kelas yang sudah ada (`.kartu`, `.isian`, `.tombol`), teks Indonesia.

- Label role lewat `User::roleLabel()` di semua tampilan.
- Input area: `<input list="areas">` + `<datalist>`. Validasi server `Areas::rule()`, pesan "Pilih area dari daftar". Area boleh kosong.
- Form `Users` (superadmin): area untuk koordinator dan sales.
- Form `Sales` (koordinator): kolom area tidak tampil, diganti teks "Area: {area}". Koordinator tanpa area: tombol simpan nonaktif, pesan "Area kamu belum diatur, hubungi superadmin".
- Kolom "Area" di daftar Toko, Sales, Users. Area null ditampilkan "Tanpa area" (pudar).
- Filter dropdown area hanya untuk superadmin (Toko, Sales, Report, Performa, Peta). Judul halaman koordinator menampilkan area, misalnya "Toko · Kab. Malang".
- Halaman baru `admin/area` (superadmin-only): tabel bertingkat Area → Koordinator → Sales, dengan `<details>` native. Kolom: jumlah toko, jumlah kunjungan, terjual, tersisa. Angka dari `VisitReport::totals()` dengan `GROUP BY stores.area` dan `visits.user_id`. Query aggregate dites di MySQL (`ONLY_FULL_GROUP_BY`).

## 4. Urutan implementasi dan test

Tests dulu, MySQL (`ministo_test`).

Urutan:
1. Migration, `Areas`, `User`/`Store` (fillable, `roleLabel`, `canAccessArea`, `visibleTo`) beserta test unit-nya.
2. Aturan penyalinan area toko (create, assignedTo, reassign).
3. Scope di Toko, Sales, `StoreShow`, `StoreCreate`, `VisitForm`.
4. Scope di Dashboard, Peta, Performa, Report, CSV (`VisitReport`).
5. Route produk superadmin-only, halaman `Users` superadmin-only.
6. UI: label role, input area, kolom dan filter area.
7. Halaman rollup `admin/area`.
8. `vendor/bin/pint`, build ulang aset (`public/build` di-commit), full test suite.

Test yang wajib ada:
- Koordinator area A tidak melihat toko, sales, atau kunjungan area B di setiap halaman.
- Koordinator tanpa area melihat data kosong (bukan data berarea null).
- Aksi dengan id area lain mengembalikan 403 (delete, restore, reassign toko; edit dan nonaktifkan sales; `VisitForm`).
- Koordinator tidak bisa membuat sales di area lain atau mengubah area sales ke area lain.
- Koordinator mendapat 403 di `admin/produk` dan `admin/users`.
- Nilai area di luar daftar ditolak.
- Toko dibuat, `assignedTo`, dan reassign menyalin `area` dengan benar. Mengubah area sales tidak mengubah area toko lama.
- `VisitReport::totals()`, halaman Report, dan CSV konsisten dengan hasil ter-scope.
- Rollup `admin/area`: angka per area, koordinator, dan sales benar. Koordinator mendapat 403.

## Di luar cakupan

- Tabel area yang dikelola lewat UI (daftar hardcode sementara).
- Relasi eksplisit koordinator ↔ sales.
- Perubahan nilai `role` di DB.
- Riwayat perubahan area.

## Catatan

- Test Livewire (`Livewire::test()->call()`) menonaktifkan middleware, jadi `assertSessionHas()` untuk flash tidak bekerja. Assert pada efek di DB.
- Docker: `docker compose exec app php artisan migrate`, tes lewat `docker compose exec app php artisan test`.
