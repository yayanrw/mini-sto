# Deploy ke cPanel

Docker hanya untuk development. Di produksi aplikasi jalan sebagai PHP biasa di
cPanel — tidak butuh Node, tidak butuh container.

## Syarat hosting

- PHP **8.2+** dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`,
  `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`
- MySQL 5.7+ / MariaDB 10.3+
- AutoSSL / sertifikat HTTPS aktif — **tanpa HTTPS, `navigator.geolocation` mati
  dan sales tidak bisa mengambil titik toko**

## Langkah

### 1. Build asset di komputer lokal

```bash
docker compose run --rm vite npm run build
```

Hasilnya di `public/build/` dan wajib ikut ter-upload (server tidak menjalankan npm).

### 2. Upload

Upload seluruh folder project ke `~/mini-sto` — **di luar** `public_html`.
Boleh lewat File Manager (zip lalu extract) atau SFTP.

### 3. Arahkan document root

Menu **Domains → domain → Document Root** → isi `mini-sto/public`.

Kalau hosting tidak mengizinkan mengubah document root:

1. Salin isi `public/` ke `public_html/`
2. Edit `public_html/index.php`, ubah dua path:

```php
require __DIR__.'/../mini-sto/vendor/autoload.php';
$app = require_once __DIR__.'/../mini-sto/bootstrap/app.php';
```

### 4. Dependency

Kalau ada Terminal / SSH:

```bash
cd ~/mini-sto
composer install --no-dev --optimize-autoloader
```

Kalau tidak ada Terminal: jalankan perintah itu di lokal lalu upload folder
`vendor/` apa adanya.

### 5. Database & .env

Buat database + user MySQL lewat **MySQL Databases**, lalu salin
`.env.example` menjadi `.env` dan isi:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com
APP_TIMEZONE=Asia/Jakarta

DB_HOST=localhost
DB_DATABASE=cpaneluser_ministo
DB_USERNAME=cpaneluser_ministo
DB_PASSWORD=...
```

`DB_HOST` di cPanel adalah `localhost`, bukan `db` seperti di Docker.

### 6. Inisialisasi

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force     # hanya sekali, membuat akun awal
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Segera ganti kata sandi keempat akun seeder setelah login pertama** — kata
sandinya `password` dan tercatat di repo ini.

### 7. Cron

**Cron Jobs** → tiap menit:

```
php /home/cpaneluser/mini-sto/artisan schedule:run >/dev/null 2>&1
```

### 8. Foto toko

`storage:link` membuat symlink `public/storage`. Sebagian hosting memblokir
symlink; kalau foto tidak tampil, ubah `config/filesystems.php` pada disk
`public`:

```php
'root' => base_path('../public_html/storage'),
```

lalu buat folder itu manual dan set permission 755.

## Update versi berikutnya

```bash
# lokal
docker compose run --rm vite npm run build

# upload perubahan, lalu di server
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`config:cache` membuat `.env` berhenti dibaca saat runtime — selalu jalankan
ulang setelah mengubah `.env`.
