# Restyle UI: hangat & ramah

Status: **selesai dieksekusi**. Catatan penyimpangan ada di bagian
[Hasil eksekusi](#hasil-eksekusi) di bawah.

## Context

Aplikasi sudah jalan penuh (sales + admin + report, 11 test hijau). Yang kurang
tampilannya: palet slate/putih bawaan Tailwind, sudut seragam, dan font sistem —
terlihat seperti dashboard internal siapa saja. Padahal penggunanya pemilik usaha
kecil dan sales yang keliling warung dengan HP murah di bawah matahari.

Target: UI terasa hangat dan ramah, tetap cepat dibaca di lapangan, tanpa
mengubah satu pun alur yang sudah terbukti jalan.

Keputusan yang sudah dikunci:
- **Kedalaman**: recolor + type + satu elemen signature (kartu "nota")
- **Warna**: bebas dipilih, belum ada warna brand produk
- **Cakupan**: semua layar (14 view + layout)

Sisa temuan yang menguntungkan: `--font-sans: 'Instrument Sans'` sudah
dideklarasi di `resources/css/app.css` tapi font-nya **tidak pernah dimuat** —
sekarang semua halaman render dengan font sistem. Jadi memasang tipografi yang
benar itu murni penambahan, bukan penggantian.

---

## Arah desain

Bukan krem-Skandinavia. Rujukannya **nota kembar** — buku struk karbon yang
dipakai tiap toko kelontong di Indonesia, dan yang persis digantikan aplikasi
ini. Kertas manila hangat, tinta cokelat-hitam, aksen dari warna makanan
(pandan, kunyit), bukan dari palet startup.

### Warna — 6 token

| Token | Hex | Peran |
|---|---|---|
| `--color-kertas` | `#FBF3E3` | latar halaman — manila, lebih kuning dari krem oat generik |
| `--color-kartu` | `#FFFDF8` | permukaan kartu |
| `--color-tinta` | `#2A2318` | teks utama — cokelat-hitam, **bukan** slate |
| `--color-daun` | `#1B7A50` | aksi utama, brand, angka **terjual** |
| `--color-kunyit` | `#E5A200` | angka **titipan/tambah**, perhatian |
| `--color-bata` | `#C0432A` | error dan aksi merusak saja |

Teks sekunder dan garis tidak jadi token sendiri — pakai `text-tinta/60` dan
`border-tinta/10`. Ini yang menghapus 78 `text-slate-500` dan 71 `border-slate-*`
tanpa menambah kosakata.

Warna teks adalah tempat rasa dingin diam-diam kembali; mengganti slate → tinta
memberi 70% efek "hangat" sebelum satu aksen pun dipasang.

### Tipografi — 2 keluarga, self-host

- **Plus Jakarta Sans** (body, UI, angka tabel). Buatan Tokotype, dirancang untuk
  identitas kota Jakarta. Humanis dan hangat, angka tabularnya rapi. Dipilih
  karena asal-usulnya nyambung dengan subjek, bukan karena aman.
- **Bricolage Grotesque** (display: judul halaman, angka besar, baris TERJUAL).
  Variabel dengan sumbu lebar — berkarakter tanpa jadi lucu-lucuan.

Self-host `.woff2` di `resources/fonts/`, `@font-face` + `font-display: swap` di
`app.css`, di-fingerprint Vite. Tanpa CDN: sinyal lapangan jelek, dan cPanel
tidak perlu dependensi eksternal. Dua file variabel, ±160 KB total.

Skala naik untuk pemakaian di luar ruangan: body 16px, isian 17px, input angka
20px tabular, target sentuh ≥44px.

### Signature — kartu "nota"

Satu tempat berani, sisanya diam. Kartu produk di form kunjungan disusun seperti
baris struk, dengan garis putus tepat di atas angka turunan:

```
╭────────────────────────────╮
│ Keripik Singkong Original  │
│                            │
│ Titipan lalu          18   │
│ Sisa dihitung    ─  8  ─   │ ← input
│ ┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈ │
│ TERJUAL               12   │ ← hasil, hijau daun
│ Tambah titipan   ─ 10  ─   │ ← input
│ ┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈ │
│ Stok ditinggal        18   │
╰┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈╯ ← tepi perforasi
```

Ini bukan hiasan: garis di atas TERJUAL mengkodekan satu-satunya ide non-obvious
di domain ini — terjual itu **selisih**, bukan sesuatu yang diketik sales.
Sekarang tiga angka tampil setara sehingga hubungan itu hilang.

Dipakai **hanya** di dua tempat: `visit-form` dan kartu riwayat di
`visit-history`. Tabel admin, dashboard, dan CRUD tetap tenang.

Perforasi: `repeating-radial-gradient` pada strip 10px di tepi bawah kartu.
Kalau rendering-nya rewel di Safari iOS, garis putus internal saja sudah membawa
idenya — turunkan tanpa menahan pekerjaan lain.

---

## Cara kerja: token dulu, view menyusul

Pola berulang di view sekarang: 47× `rounded-xl border border-slate-300`
(isian), 24× `rounded-2xl border border-slate-200` (kartu), 8× tombol utama.
Diangkat jadi class CSS asli di `@layer components` — view jadi **lebih pendek**,
bukan lebih panjang.

```
resources/css/app.css          ← seluruh sistem: @theme, @font-face, komponen
resources/fonts/*.woff2        ← baru
resources/views/components/layout.blade.php   ← latar, header admin, bottom nav
resources/views/livewire/sales/visit-form.blade.php      ← signature nota
resources/views/livewire/sales/visit-history.blade.php   ← signature nota
+ 11 view lain               ← tukar token & class, mekanis
```

Class yang disediakan: `.kartu`, `.isian`, `.tombol`, `.tombol-utama`, `.nota`,
`.perforasi`, `.label-kecil`. Ditulis CSS biasa dengan `var()`, bukan `@apply` —
menghindari urutan spesifisitas yang saling membatalkan.

**Peta**: tile OpenStreetMap abu-kebiruan akan bentrok dengan kertas hangat. Satu
baris `filter: sepia(.18) saturate(.9)` pada `.leaflet-tile` mendudukkannya di
palet yang sama. Murah, dan gampang dicabut kalau mengganggu keterbacaan jalan.

---

## Pass copy

Kata adalah material desain, bukan hiasan. Sekalian rapikan yang menjelaskan
sistem alih-alih menjelaskan pekerjaan:

| Sekarang | Jadi | Alasan |
|---|---|---|
| "Sisa sekarang" | "Sisa dihitung" | menamai yang sales lakukan |
| "Terjual 0 · Stok setelah kunjungan 0" | baris nota terpisah | satu elemen satu tugas |
| "Belum ada toko. Tambah lewat tombol '+ Toko Baru'." | "Belum ada toko di daftar." + tombol di dalam kartu kosong | layar kosong itu ajakan bertindak |
| "Toko ini belum punya koordinat." | "Toko ini belum punya titik lokasi. Admin bisa mengisinya dari menu Toko." | error menyebut jalan keluar |

Nama aksi konsisten dari tombol sampai notifikasi: "Simpan kunjungan" →
"Kunjungan tersimpan".

---

## Lantai kualitas

- Kontras: tinta di kertas ±13:1. **Verifikasi `--color-daun` untuk teks dan
  tombol ≥4.5:1** — kalau kurang, gelapkan ke `#17694A`.
- `:focus-visible` ring hijau daun di semua kontrol — sekarang tidak ada sama sekali.
- `prefers-reduced-motion` dihormati untuk transisi apa pun yang ditambahkan.
- Responsif turun ke 360px; tabel admin tetap `overflow-x-auto` seperti sekarang.
- Tanpa dark mode — tidak diminta, dan menggandakan permukaan yang harus dicek.

---

## Verifikasi

1. `docker compose exec app php artisan test` — 11 test harus tetap hijau
   (assert status 200; perubahan class tak boleh menyentuhnya).
2. `docker compose run --rm vite npm run build` — pastikan font ikut ter-bundle
   dan `manifest.json` menyebut file woff2.
3. Screenshot Chrome di **390px** dan **1280px** untuk: login, daftar toko,
   form kunjungan (signature), riwayat kunjungan, dashboard admin, report.
4. Tab keyboard menyusuri form kunjungan — ring fokus harus terlihat di tiap
   input dan tombol.
5. Buka `/admin/peta` — pastikan filter tile tidak membuat nama jalan sulit dibaca.
6. Cek kontras token final dengan pengukur rasio sebelum menutup pekerjaan.

---

## Hasil eksekusi

Yang berbeda dari rencana, beserta alasannya:

| Rencana | Yang dikerjakan | Kenapa |
|---|---|---|
| Unduh `.woff2` manual ke `resources/fonts/` | `@fontsource-variable/*` lewat npm | Tetap self-host penuh (di-bundle Vite, tanpa CDN), tapi tanpa mengurus subset dan `@font-face` sendiri |
| `--color-kunyit: #E5A200` | `#8F5E00` untuk teks, `#E5A200` disimpan sebagai `--color-kunyit-terang` untuk bidang besar | Diukur 4.18:1 di kartu — di bawah AA. Versi gelap 5.48:1 |
| Teks sekunder `text-tinta/60` | `text-tinta/70` (47 tempat) | 60% hanya 4.39:1. 70% jadi 5.76:1 |
| — | `resources/views/welcome.blade.php` dihapus | Sisa scaffold Laravel, tak lagi dirujuk route mana pun, dan satu-satunya file yang masih memuat palet lama |

Angka akhir kontras (rasio terhadap permukaan kartu `#FFFDF8`):

| Pasangan | Rasio |
|---|---|
| `tinta` / `kertas` | 14.08 |
| `tinta` / `kartu` | 15.28 |
| `daun` teks | 6.55 |
| putih di atas tombol `daun` | 6.65 |
| `kunyit` | 5.48 |
| `bata` | 5.95 |
| teks sekunder `tinta/70` | 5.76 |

Terverifikasi: 11 test tetap hijau, 12 route balas 200, font ikut ter-bundle
(6 file `.woff2` di `manifest.json`), ring fokus tampil saat tab keyboard,
tile peta terbaca setelah difilter. CSS turun 57.8 KB → 51.1 KB karena
pola berulang pindah ke `@layer components`.

---

## Yang sengaja tidak dikerjakan

| Ditunda | Kerjakan kalau |
|---|---|
| Dark mode | ada yang benar-benar memakai aplikasi di gelap |
| Ilustrasi / maskot layar kosong | teks + tombol terbukti kurang mengarahkan |
| Animasi transisi halaman | Livewire `navigate` terasa patah tanpanya |
| Restyle struktur dashboard | angka polos terbukti tidak terbaca, bukan sekadar kurang cantik |
