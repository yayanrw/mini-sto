# mini-sto

Pencatatan distribusi produk konsinyasi. Sales keliling toko, mencatat sisa
titipan dan menambah titipan baru; owner melihat produknya ada di mana saja
lewat peta, dashboard, dan report.

Laravel 12 · Livewire 3 · Tailwind 4 · MySQL 8 · Leaflet/OpenStreetMap · PHP 8.2

Tampilannya mengambil rujukan dari **nota kembar** — buku struk karbon yang
dipakai tiap toko kelontong, dan yang persis digantikan aplikasi ini. Sistem
warna dan komponennya ada di satu file: `resources/css/app.css`.

## Menjalankan (Docker)

```bash
docker compose up -d              # app :8000, vite :5173, mysql :3307
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

Buka http://localhost:8000

Akun seeder (kata sandi semua: `password`):

| Email | Role |
|---|---| 
| super@ministo.test | superadmin |
| admin@ministo.test | admin |
| budi@ministo.test | sales |
| sari@ministo.test | sales |

Perintah lain:

```bash
docker compose exec app php artisan test    # butuh database ministo_test
docker compose run --rm vite npm run build  # build asset produksi
```

Database test dibuat sekali:

```bash
docker compose exec db mysql -uroot -psecret \
  -e "CREATE DATABASE IF NOT EXISTS ministo_test; GRANT ALL ON ministo_test.* TO 'ministo'@'%';"
```

## Model data

Stok titipan sebuah toko tidak disimpan di tabel terpisah — sumber kebenarannya
adalah riwayat kunjungan. Tiap baris `visit_items` menyimpan:

| Kolom | Arti |
|---|---|
| `qty_before` | stok akhir kunjungan sebelumnya |
| `qty_found` | sisa yang dihitung sales |
| `qty_sold` | `qty_before - qty_found` |
| `qty_added` | titipan baru |
| `qty_left` | `qty_found + qty_added` — jadi `qty_before` kunjungan berikutnya |

`app/Actions/RecordVisit.php` adalah satu-satunya jalan menulis kunjungan; semua
aturan (sisa tidak boleh melebihi titipan, produk bersisa wajib dihitung ulang)
ada di sana.

## Peran

| | Sales | Admin | Superadmin |
|---|---|---|---|
| Toko baru, kunjungan | ✅ | ✅ | ✅ |
| Peta, dashboard, performa, report | — | ✅ | ✅ |
| Master produk & toko | — | ✅ | ✅ |
| Kelola pengguna | — | — | ✅ |

## Deploy

Lihat [DEPLOY.md](DEPLOY.md) untuk cPanel.

## Dokumen

- [docs/ui-restyle-plan.md](docs/ui-restyle-plan.md) — arah desain UI, token warna, dan hasil pengukuran kontras
