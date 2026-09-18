<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WipeExceptSuperadmin extends Command
{
    protected $signature = 'app:wipe-except-superadmin {--force : Skip confirmation}';

    protected $description = 'Delete all data (visits, stores, products, non-superadmin users) except superadmin accounts';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Ini akan hapus SEMUA data kecuali akun superadmin. Lanjut?')) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            VisitItem::query()->delete();
            Visit::query()->delete();
            Store::query()->delete();
            Product::query()->delete();
            User::where('role', '!=', 'superadmin')->delete();
        });

        $this->info('Selesai. Data terhapus kecuali akun superadmin.');

        return self::SUCCESS;
    }
}
